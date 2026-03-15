<?php

namespace App\Services\Sales;

use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use App\Models\Sales\Quotation;
use App\Models\Marketing\PromoCode;
use App\Mail\QuotationSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class QuotationService
{
    public function sendQuotationEmail(Quotation $quotation): bool
    {
        $emailTarget = $quotation->deal?->customer?->email ?? $quotation->deal?->lead?->email;

        if (!$emailTarget) {
            return false;
        }

        Mail::to($emailTarget)->send(new QuotationSent($quotation));
        $quotation->update(['status' => 'sent']);

        return true;
    }

    public function validatePromoCode(?string $code): ?PromoCode
    {
        if (empty($code)) return null;

        return PromoCode::where('code', $code)
            ->where('is_active', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();
    }

    public function recalculateFormData(array $data): array
    {
        $subtotal = 0;

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as &$item) {
                // Backend Fail-safe for item details
                if (!empty($item['item_id'])) {
                    $type = $item['item_type'] ?? 'product';
                    $model = match ($type) {
                        'product' => \App\Models\Inventory\Product::find($item['item_id']),
                        'service' => \App\Models\Inventory\Service::find($item['item_id']),
                        'package' => \App\Models\Inventory\Package::find($item['item_id']),
                        default => null
                    };

                    if ($model) {
                        $item['item_code'] = $item['item_code'] ?? ($model->product_code ?? $model->service_code ?? $model->package_code ?? $model->code);
                        $item['item_name'] = $item['item_name'] ?? ($model->product_name ?? $model->service_name ?? $model->package_name ?? $model->name);
                    }
                }

                $qty = (float) ($item['qty'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);
                $item['line_total'] = $qty * $price;

                $subtotal += $item['line_total'];
            }
        }

        $data['subtotal'] = $subtotal;

        $totalDiscount = 0;
        if (!empty($data['promo_code_id'])) {
            $promo = PromoCode::find($data['promo_code_id']);
            if ($promo) {
                if ($promo->type === 'percentage') {
                    $totalDiscount = $subtotal * ((float)$promo->value / 100);
                } else {
                    $totalDiscount = (float)$promo->value;
                }
            }
        }

        $data['discount_amount'] = min($totalDiscount, $subtotal);

        $taxPercent = (float) ($data['tax'] ?? 0);
        $afterDiscount = $subtotal - $data['discount_amount'];
        $data['grand_total'] = $afterDiscount + ($afterDiscount * ($taxPercent / 100));

        return $data;
    }

    public function createQuotation(array $data): Quotation
    {
        return DB::transaction(function () use ($data) {
            $data = $this->recalculateFormData($data);

            $items = $data['items'] ?? [];
            $quotationData = \Illuminate\Support\Arr::except($data, ['items', 'promo_code_input', 'temp_discount_type', 'temp_discount_value']);

            $quotation = Quotation::create($quotationData);

            foreach ($items as $item) {
                $quotation->items()->create($item);
            }

            $this->syncDealAfterCreation($quotation);

            return $quotation;
        });
    }

    public function updateQuotation(Quotation $quotation, array $data): Quotation
    {
        return DB::transaction(function () use ($quotation, $data) {
            $data = $this->recalculateFormData($data);

            $items = $data['items'] ?? [];
            $quotationData = \Illuminate\Support\Arr::except($data, ['items', 'promo_code_input', 'temp_discount_type', 'temp_discount_value']);

            $oldStatus = $quotation->status;
            $newStatus = $quotationData['status'] ?? $oldStatus;

            $quotation->update($quotationData);

            $quotation->items()->delete();
            foreach ($items as $item) {
                $quotation->items()->create($item);
            }

            if ($oldStatus !== 'accepted' && $newStatus === 'accepted') {
                $this->handleQuotationAccepted($quotation);
            } elseif ($oldStatus !== 'rejected' && $newStatus === 'rejected') {
                $this->handleQuotationRejected($quotation);
            }

            return $quotation;
        });
    }

    public function syncDealAfterCreation(Quotation $quotation): void
    {
        if (!$quotation->nx_deal_id) return;

        $deal = Deal::find($quotation->nx_deal_id);
        if (!$deal) return;

        $updateData = [];
        $penawaranStage = DealStage::where('name', 'like', '%Penawaran%')->first();

        if ($penawaranStage) {
            $updateData['nx_deal_stage_id'] = $penawaranStage->id;
        }

        if ($deal->status === 'lost') {
            $updateData['status'] = 'open';
            $updateData['close_date'] = null;

            Notification::make()
                ->title('Deal Dibuka Kembali')
                ->body("Status Deal {$deal->deal_number} otomatis berubah dari Lost menjadi Open.")
                ->info()->send();
        }

        if (!empty($updateData)) {
            $deal->update($updateData);
        }
    }

    public function approveQuotation(Quotation $record, $userId, $employeeId): void
    {
        $record->update([
            'status' => 'accepted',
            'approved_by' => $employeeId,
            'approved_at' => now(),
        ]);

        $this->handleQuotationAccepted($record);
    }

    public function rejectQuotation(Quotation $record, $employeeId, string $reason): void
    {
        $record->update([
            'status' => 'rejected',
            'approved_by' => $employeeId,
            'approved_at' => now(),
            'notes' => trim(($record->notes ? $record->notes . "\n" : '') . "Alasan Rejected: " . $reason),
        ]);

        $this->handleQuotationRejected($record);
    }

    public function handleQuotationAccepted(Quotation $quotation): void
    {
        $quotation->loadMissing(['deal.lead']);
        $deal = $quotation->deal;

        if (!$deal || !$deal->exists) return;

        DB::transaction(function () use ($deal) {
            $wonStage = DealStage::where('name', 'like', '%Won%')->first();

            $deal->update([
                'status' => 'won',
                'nx_deal_stage_id' => $wonStage?->id,
                'close_date' => now(),
            ]);

            $lead = $deal->lead;
            if ($lead && empty($deal->nx_customer_id)) {
                $customer = $lead->convertToCustomer();
                if ($customer) {
                    $deal->update(['nx_customer_id' => $customer->id]);
                }
            }
        });

        Notification::make()
            ->title('Deal Won!')
            ->body("Deal {$deal->deal_number} berhasil ditutup (Won) dan dikonversi ke Customer.")
            ->success()->send();
    }

    public function handleQuotationRejected(Quotation $quotation): void
    {
        $deal = Deal::find($quotation->nx_deal_id);
        $lostStage = DealStage::where('name', 'like', '%Lost%')->first();

        if ($deal) {
            $deal->update([
                'status' => 'lost',
                'nx_deal_stage_id' => $lostStage?->id,
                'close_date' => now(),
            ]);

            Notification::make()
                ->title('Deal Lost')
                ->body("Deal {$deal->deal_number} ditandai sebagai Lost.")
                ->danger()->send();
        }
    }
}
