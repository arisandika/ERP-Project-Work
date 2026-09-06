<?php

namespace App\Filament\Resources\AfterSales;

use App\Filament\Resources\AfterSales\VendorClaimResource\Pages;
use App\Models\AfterSales\ReturnRequest;
use App\Models\AfterSales\VendorClaim;
use App\Services\ReturnService;
use App\Services\AfterSales\ReturnWorkflowService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\BelongsToModule;

class VendorClaimResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'inventory';
    protected static ?string $model = VendorClaim::class;
    protected static ?string $modelLabel = 'Klaim Vendor';
    protected static ?string $pluralModelLabel = 'Daftar Klaim Vendor';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Manajemen After-Sales';
    protected static ?string $navigationLabel = 'Klaim Vendor';
    protected static ?string $slug = 'after-sales/vendor-claims';
    protected static ?int $navigationSort = 23;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rma.rma_number')
                    ->label('No. RMA')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rma.serialNumber.serial_number')
                    ->label('SN Unit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),

                Tables\Columns\TextColumn::make('purchaseOrder.po_number')
                    ->label('No. PO Claim')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        VendorClaim::STATUS_SENT      => 'warning',
                        VendorClaim::STATUS_COMPLETED => 'success',
                        VendorClaim::STATUS_REJECTED  => 'danger',
                        default                       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => VendorClaim::getStatusLabels()[$state] ?? $state),
            ])
            ->actions([
                // 1. TERIMA DARI VENDOR (sent -> completed) — vendor mengirim penggantian/unit kembali
                Tables\Actions\Action::make('receive_from_vendor')
                    ->label('Terima dari Vendor')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (VendorClaim $record) => $record->status === VendorClaim::STATUS_SENT)
                    ->form([
                        Forms\Components\Radio::make('resolution_type')
                            ->label('Hasil Klaim Supplier')
                            ->options([
                                ReturnRequest::RESOLUTION_REPAIR_AND_RETURN => 'Repair (unit sama, kembali ke customer)',
                                ReturnRequest::RESOLUTION_REPLACEMENT       => 'Replace (vendor kirim unit baru)',
                            ])
                            ->inline()->live()->required(),
                        Forms\Components\TextInput::make('new_serial_number')
                            ->label('SN Baru dari Vendor')
                            ->visible(fn (Forms\Get $get) => $get('resolution_type') === ReturnRequest::RESOLUTION_REPLACEMENT)
                            ->required(fn (Forms\Get $get) => $get('resolution_type') === ReturnRequest::RESOLUTION_REPLACEMENT)
                            ->unique('nx_serial_number', 'serial_number'),
                        Forms\Components\Textarea::make('vendor_notes')->required(),
                    ])
                    ->action(function (VendorClaim $record, array $data) {
                        app(ReturnWorkflowService::class)->receiveFromVendor($record->rma, $data);

                        \Filament\Notifications\Notification::make()
                            ->title('Klaim Vendor Selesai')
                            ->body("RMA {$record->rma->rma_number} barang diterima dari vendor.")
                            ->success()->send();
                    }),

                // 2. TOLAK KLAIM VENDOR — keputusan garansi saja, unit tetap di vendor.
                Tables\Actions\Action::make('reject_vendor_claim')
                    ->label('Klaim Ditolak Vendor')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (VendorClaim $record) => $record->status === VendorClaim::STATUS_SENT)
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Klaim Vendor')
                    ->modalDescription('Vendor menolak garansi. Unit masih di vendor — konfirmasi penerimaan unit dilakukan aksi terpisah.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Penolakan Vendor')
                            ->required(),
                    ])
                    ->action(function (VendorClaim $record, array $data) {
                        app(ReturnWorkflowService::class)->rejectVendorClaim($record->rma, $data['reason']);

                        \Filament\Notifications\Notification::make()
                            ->title('Klaim Ditolak Vendor')
                            ->body("RMA {$record->rma->rma_number} klaim vendor ditolak. Menunggu unit kembali.")
                            ->success()->send();
                    }),

                // 3. UNIT DITOLAK KEMBALI KE TOKO — fisik unit diterima kembali → RMA siap kembali
                Tables\Actions\Action::make('return_rejected_unit')
                    ->label('Unit Ditangguhkan Diterima')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->visible(fn (VendorClaim $record) => $record->status === VendorClaim::STATUS_REJECTED)
                    ->requiresConfirmation()
                    ->modalHeading('Terima Unit dari Vendor')
                    ->modalDescription('Unit yang klaimnya ditolak vendor sudah benar-benar diterima kembali. RMA siap dikembalikan ke klien.')
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Penerimaan Unit')
                            ->rows(3),
                    ])
                    ->action(function (VendorClaim $record, array $data) {
                        app(ReturnWorkflowService::class)->returnRejectedUnit($record->rma, $data['notes']);

                        \Filament\Notifications\Notification::make()
                            ->title('Unit Diterima Kembali')
                            ->body("RMA {$record->rma->rma_number} unit kembali dari vendor. Siap dikembalikan ke klien.")
                            ->success()->send();
                    }),
            ])
            ->emptyStateHeading('Tidak ada klaim vendor aktif');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'warehouse_employees']);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorClaims::route('/'),
        ];
    }
}