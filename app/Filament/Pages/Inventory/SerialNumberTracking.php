<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Concerns\BelongsToModule;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\StockTransaction;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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

    /**
     * Resolusi Konflik Trait
     * Menggabungkan Shield untuk Permission User dan BelongsToModule untuk Lisensi Modul.
     */
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'inventory';

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

    /**
     * Integrasi Validasi Akses Modul & Shield
     */
    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    public function mount(): void
    {
        $this->form->fill();
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

        // Reset state sebelum pencarian baru
        $this->resetResults();

        if (empty($serialNumber)) {
            Notification::make()
                ->title('Input diperlukan')
                ->warning()
                ->send();
            return;
        }

        // Optimized Query dengan Eager Loading
        $record = SerialNumber::query()
            ->with([
                'product:id,product_name,product_code',
                'warehouse:id,warehouse_name',
                'supplier:id,supplier_name,name',
                'purchaseOrder:id,po_number,purchase_order_number',
                'customer:id,customer_name,name',
            ])
            ->where('serial_number', $serialNumber)
            ->first();

        if (! $record) {
            $this->handleNotFound($serialNumber);
            return;
        }

        $this->processTrackingResult($record);
        $this->loadTransactionHistory($record->id);
    }

    protected function processTrackingResult(SerialNumber $record): void
    {
        $warrantyExpiredAt = $record->warranty_expired_at ? Carbon::parse($record->warranty_expired_at) : null;

        $this->trackingResult = [
            'serial_number' => $record->serial_number,
            'product_name' => $record->product->product_name ?? '-',
            'product_code' => $record->product->product_code ?? '-',
            'warehouse_name' => $record->warehouse->warehouse_name ?? '-',
            'status' => str($record->status)->replace('_', ' ')->title(),
            'supplier_name' => $record->supplier->supplier_name ?? $record->supplier->name ?? '-',
            'purchase_order_number' => $record->purchaseOrder->po_number ?? $record->purchaseOrder->purchase_order_number ?? '-',
            'customer_name' => $record->customer->customer_name ?? $record->customer->name ?? '-',
            'inbound_date' => $record->inbound_date ? Carbon::parse($record->inbound_date)->format('d M Y') : '-',
            'outbound_date' => $record->outbound_date ? Carbon::parse($record->outbound_date)->format('d M Y') : 'Stok Tersedia',
            'warranty_expired_at' => $warrantyExpiredAt?->format('d M Y') ?? '-',
            'warranty_status' => $warrantyExpiredAt
                ? ($warrantyExpiredAt->isFuture() || $warrantyExpiredAt->isToday() ? 'Aktif' : 'Expired')
                : '-',
        ];
    }

    protected function loadTransactionHistory($serialNumberId): void
    {
        $this->transactionHistory = StockTransaction::query()
            ->with(['warehouse:id,warehouse_name', 'creator:id,name'])
            ->where('serial_number_id', $serialNumberId)
            ->orderByDesc('transaction_date')
            ->get()
            ->map(fn ($item) => [
                'transaction_code' => $item->transaction_code ?: '-',
                'transaction_date' => $item->transaction_date ? Carbon::parse($item->transaction_date)->format('d M Y H:i') : '-',
                'mutation_type' => str($item->mutation_type)->replace('_', ' ')->title(),
                'reference_number' => $item->reference_number ?: '-',
                'warehouse_name' => $item->warehouse->warehouse_name ?? '-',
                'created_by' => $item->creator->name ?? '-',
                'notes' => $item->notes ?: '-',
            ])
            ->toArray();
    }

    protected function resetResults(): void
    {
        $this->trackingResult = null;
        $this->transactionHistory = [];
        $this->notFound = false;
    }

    protected function handleNotFound(string $sn): void
    {
        $this->notFound = true;
        Notification::make()
            ->title('SN tidak ditemukan')
            ->body("Serial Number {$sn} tidak terdaftar dalam database.")
            ->danger()
            ->send();
    }

    public function resetSearch(): void
    {
        $this->resetResults();
        $this->form->fill(['serial_number' => null]);
    }
}
