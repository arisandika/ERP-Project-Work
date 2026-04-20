<?php

namespace App\Filament\Pages\Inventory;

use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\StockTransaction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class SerialNumberTracking extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Pelacakan Serial Number';
    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?int $navigationSort = 11;
    protected static ?string $title = 'Pelacakan Serial Number';

    protected static string $view = 'filament.pages.inventory.serial-number-tracking';

    public ?array $data = [];
    public ?array $trackingResult = null;
    public array $transactionHistory = [];
    public bool $notFound = false;

    public function mount(): void
    {
        $this->form->fill([
            'serial_number' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('serial_number')
                    ->label('Serial Number')
                    ->placeholder('Scan atau ketik SN, contoh: SN009')
                    ->required()
                    ->autofocus()
                    ->extraInputAttributes([
                        'wire:keydown.enter.prevent' => 'search',
                    ]),
            ])
            ->statePath('data');
    }

    public function search(): void
    {
        $serialNumber = trim((string) ($this->data['serial_number'] ?? ''));

        $this->trackingResult = null;
        $this->transactionHistory = [];
        $this->notFound = false;

        if ($serialNumber === '') {
            Notification::make()
                ->title('Serial Number wajib diisi')
                ->warning()
                ->send();

            return;
        }

        $record = SerialNumber::query()
            ->with([
                'product',
                'warehouse',
                'supplier',
                'purchaseOrder',
                'customer',
            ])
            ->where('serial_number', $serialNumber)
            ->first();

        if (! $record) {
            $this->notFound = true;

            Notification::make()
                ->title('SN tidak ditemukan')
                ->body("Serial Number {$serialNumber} tidak ada di sistem.")
                ->danger()
                ->send();

            return;
        }

        $warrantyExpiredAt = $record->warranty_expired_at
            ? Carbon::parse($record->warranty_expired_at)
            : null;

        $this->trackingResult = [
            'serial_number' => $record->serial_number,
            'product_name' => $record->product->product_name ?? '-',
            'product_code' => $record->product->product_code ?? '-',
            'warehouse_name' => $record->warehouse->warehouse_name ?? '-',
            'status' => ucwords(strtolower(str_replace('_', ' ', (string) ($record->status ?? '-')))),
            'supplier_name' => $record->supplier->supplier_name ?? $record->supplier->name ?? '-',
            'purchase_order_number' => $record->purchaseOrder->po_number ?? $record->purchaseOrder->purchase_order_number ?? '-',
            'customer_name' => $record->customer->customer_name ?? $record->customer->name ?? '-',
            'inbound_date' => $record->inbound_date ? Carbon::parse($record->inbound_date)->format('d M Y') : '-',
            'outbound_date' => $record->outbound_date ? Carbon::parse($record->outbound_date)->format('d M Y') : 'Belum Keluar',
            'warranty_expired_at' => $warrantyExpiredAt?->format('d M Y') ?? '-',
            'warranty_status' => $warrantyExpiredAt
                ? ($warrantyExpiredAt->isFuture() || $warrantyExpiredAt->isToday() ? 'Aktif' : 'Expired')
                : '-',
            'created_at' => $record->created_at?->format('d M Y H:i') ?? '-',
            'updated_at' => $record->updated_at?->format('d M Y H:i') ?? '-',
        ];

        $this->transactionHistory = StockTransaction::query()
            ->with(['warehouse', 'creator'])
            ->where('serial_number_id', $record->id)
            ->orderByDesc('transaction_date')
            ->get()
            ->map(fn ($item) => [
                'transaction_code' => $item->transaction_code ?: '-',
                'transaction_date' => $item->transaction_date
                    ? Carbon::parse($item->transaction_date)->format('d M Y H:i')
                    : '-',
                'mutation_type' => ucwords(strtolower(str_replace('_', ' ', (string) ($item->mutation_type ?? '-')))),
                'reference_number' => $item->reference_number ?: '-',
                'warehouse_name' => $item->warehouse->warehouse_name ?? '-',
                'created_by' => $item->creator->name ?? '-',
                'notes' => $item->notes ?: '-',
            ])
            ->toArray();
    }

    public function resetSearch(): void
    {
        $this->trackingResult = null;
        $this->transactionHistory = [];
        $this->notFound = false;

        $this->form->fill([
            'serial_number' => null,
        ]);
    }
}
