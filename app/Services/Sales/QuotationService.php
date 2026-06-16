<?php

namespace App\Services\Sales;

use App\Models\CRM\Customer;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use App\Models\CRM\Lead;
use App\Models\Inventory\Package;
use App\Models\Inventory\Product;
use App\Models\Inventory\Service;
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

        Mail::to($emailTarget)->queue(new QuotationSent($quotation));
        $quotation->update(['status' => 'sent']);

        return true;
    }

    public function validatePromoCode(?string $code): ?PromoCode
    {
        if (empty($code))
            return null;

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
                        'product' => Product::find($item['item_id']),
                        'service' => Service::find($item['item_id']),
                        'package' => Package::find($item['item_id']),
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
                    $totalDiscount = $subtotal * ((float) $promo->value / 100);
                } else {
                    $totalDiscount = (float) $promo->value;
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

            // Jika status dirubah lewat form edit secara manual
            if ($oldStatus !== 'accepted' && $newStatus === 'accepted') {
                $quotation->markAsAccepted(); // Pastikan is_primary trigger jalan
                $this->handleQuotationAccepted($quotation);
            } elseif ($oldStatus !== 'rejected' && $newStatus === 'rejected') {
                $quotation->markAsRejected('Ditolak secara manual via form Edit');
                $this->handleQuotationRejected($quotation);
            }

            return $quotation;
        });
    }

    public function syncDealAfterCreation(Quotation $quotation): void
    {
        if (!$quotation->nx_deal_id)
            return;

        $deal = Deal::find($quotation->nx_deal_id);
        if (!$deal)
            return;

        $updateData = [];
        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first();

        if ($penawaranStage) {
            $updateData['nx_deal_stage_id'] = $penawaranStage->id;
        }

        // Kembalikan Deal ke OPEN jika sebelumnya sudah ditutup (Lost/Won)
        if (in_array($deal->status, [Deal::STATUS_CLOSED_LOST, Deal::STATUS_CLOSED_WON])) {
            $updateData['status'] = Deal::STATUS_OPEN;
            $updateData['close_date'] = null;
            $updateData['closed_at'] = null;
        }

        if (!empty($updateData)) {
            $deal->update($updateData);
        }

        // LOGIKA BARU: Kembalikan Lead menjadi Qualified
        if ($deal->lead) {
            $deal->lead->update([
                'status' => Lead::STATUS_QUALIFIED
            ]);
        }
    }

    public function approveQuotation(Quotation $record, $userId, $employeeId): void
    {
        // Gunakan helper method markAsAccepted() untuk otomatis set status = accepted, is_primary = true, dan accepted_at = now()
        $record->markAsAccepted();

        $record->update([
            'approved_by' => $employeeId,
            'approved_at' => now(),
        ]);

        $this->handleQuotationAccepted($record);
    }

    public function rejectQuotation(Quotation $record, $employeeId, string $reason): void
    {
        // Gunakan helper method markAsRejected() untuk set is_primary = false dan reject_reason
        $record->markAsRejected($reason);

        $record->update([
            'approved_by' => $employeeId,
            'approved_at' => now(),
        ]);

        $this->handleQuotationRejected($record);
    }

    public function handleQuotationAccepted(Quotation $quotation): void
    {
        $quotation->loadMissing(['deal.lead']);
        $deal = $quotation->deal;

        if (!$deal || !$deal->exists)
            return;

        DB::transaction(function () use ($deal) {
            // 1. Konversi Lead menjadi Customer
            $lead = $deal->lead;
            if ($lead) {
                // convertToCustomer() sudah memiliki logic untuk mencegah duplikasi atau membuat riwayat duplikat baru jika Cancelled
                $customer = $lead->convertToCustomer();

                // PENTING: Pastikan Deal ini terkait ke Customer yang Active (Terbaru)
                $deal->update(['nx_customer_id' => $customer->id]);
            }

            // 2. Cari Stage Won
            $wonStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%won%'])->first();

            // 3. Update Deal: Status WON dan Isi Tanggal Penutupan
            $deal->update([
                'status' => Deal::STATUS_CLOSED_WON,
                'nx_deal_stage_id' => $wonStage?->id,
                'close_date' => now(),
                'closed_at' => now(),
            ]);
        });

        Notification::make()
            ->title('Deal Won!')
            ->body("Penawaran disetujui. Deal {$deal->deal_number} ditutup (Won) dan Customer diperbarui.")
            ->success()->send();
    }

    public function handleQuotationRejected(Quotation $quotation): void
    {
        $deal = $quotation->deal;
        if (!$deal || !$deal->exists)
            return;

        // CEK FAKTA: Apakah MASIH ADA penawaran lain yang BUKAN rejected?
        $hasOtherActiveQuotations = $deal->quotations()
            ->where('id', '!=', $quotation->id)
            ->where('status', '!=', 'rejected')
            ->exists();

        if ($hasOtherActiveQuotations) {
            return;
        }

        // Jika semua penawaran ditolak
        DB::transaction(function () use ($deal) {
            // 1. Ubah Deal Stage & Status menjadi Lost
            $lostStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%lost%'])->first();
            $deal->update([
                'status' => Deal::STATUS_CLOSED_LOST,
                'nx_deal_stage_id' => $lostStage?->id,
                'close_date' => now(),
                'closed_at' => now(),
            ]);

            // 2. LOGIKA BARU: Ubah Lead menjadi Unqualified
            if ($deal->lead) {
                $deal->lead->update([
                    'status' => Lead::STATUS_UNQUALIFIED
                ]);
            }

            // 3. Batalkan Customer (Cancelled)
            if ($deal->nx_customer_id) {
                $customer = Customer::find($deal->nx_customer_id);
                if ($customer && $customer->status !== 'cancelled') {
                    $customer->update(['status' => 'cancelled']);
                }
            }
        });

        Notification::make()
            ->title('Deal & Lead Unqualified')
            ->body("Semua penawaran ditolak. Deal {$deal->deal_number} menjadi Lost dan Lead menjadi Unqualified.")
            ->danger()->send();
    }
}
