<?php

namespace App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;

use App\Filament\Resources\AfterSales\ReturnRequestResource;
use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use App\Services\ReturnService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ListEntry;

class ViewReturnRequest extends ViewRecord
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            // 1. KIRIM KE VENDOR
            Actions\Action::make('send_to_vendor')
                ->label('Kirim ke Vendor')
                ->color('warning')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_RECEIVED && $record->warranty_type === 'supplier')
                ->form([
                    Forms\Components\DatePicker::make('sent_to_vendor_date')->default(now())->required(),
                ])
                ->action(function (ReturnRequest $record, array $data, ReturnService $service) {
                    $service->sendToVendor($record, $data['sent_to_vendor_date'] ?? now());

                    \Filament\Notifications\Notification::make()
                        ->title('Dikirim ke Vendor')
                        ->body("RMA {$record->rma_number} dikirim ke supplier. PO klaim dibuat.")
                        ->success()
                        ->send();
                }),

            // 2. TERIMA DARI VENDOR
            // 2. TERIMA DARI VENDOR
            Actions\Action::make('receive_from_vendor')
                ->label('Terima dari Vendor')
                ->color('info')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_SENT_TO_VENDOR)
                ->form([
                    Forms\Components\Radio::make('resolution_type')
                        ->options([
                            'repaired'  => 'Repair (SN Tetap)',
                            'replaced'  => 'Replace (SN Baru)',
                            'refund'    => 'Refund (Kredit/Refund dari Supplier)',
                            'rejected'  => 'Reject (SN dikembalikan ke customer, tidak diterima garansi)',
                        ])
                        ->inline()->live()->required(),
                    Forms\Components\TextInput::make('new_serial_number')
                        ->label('SN Baru')
                        ->visible(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                        ->required(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                        ->unique('nx_serial_number', 'serial_number'),
                    Forms\Components\Hidden::make('new_serial_number_id'),
                    Forms\Components\Textarea::make('vendor_notes')->required(),
                ])
                ->action(function (ReturnRequest $record, array $data, ReturnService $service) {
                    // Resolve new_serial_number_id if a replacement SN number was entered
                    if (($data['resolution_type'] ?? null) === 'replaced') {
                        $sn = SerialNumber::where('serial_number', $data['new_serial_number'])->first();
                        if ($sn) {
                            $data['new_serial_number_id'] = $sn->id;
                        }
                    }

                    $purchaseReturn = $service->receiveFromVendor($record, $data);

                    // Provide feedback to user
                    \Filament\Notifications\Notification::make()
                        ->title('Sukses')
                        ->body("Return diterima dari vendor. Purchase Return #{$purchaseReturn->return_number} dibuat.")
                        ->success()
                        ->send();
                }),

            // 3. PROSES INTERNAL (TOKO)
            Actions\Action::make('process_internal')
                ->label('Proses Internal (Repair/Replace)')
                ->color('danger')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_RECEIVED && $record->warranty_type === 'store')
                ->form([
                    Forms\Components\Radio::make('resolution_type')
                        ->options(['repaired' => 'Berhasil Diperbaiki', 'replaced' => 'Ganti Unit Baru (Dari Gudang)'])
                        ->inline()->live()->required(),
                    Forms\Components\Select::make('new_serial_number_id')
                        ->options(fn (ReturnRequest $record) => SerialNumber::where('product_id', $record->serialNumber->product_id)
                            ->where('status', 'available')
                            ->pluck('serial_number', 'id')
                        )
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                        ->required(fn (Forms\Get $get) => $get('resolution_type') === 'replaced'),
                    Forms\Components\Textarea::make('internal_notes')->required(),
                ])
                ->action(function (ReturnRequest $record, array $data, ReturnService $service) {
                    // The form already sends new_serial_number_id via Select,
                    // so $data already has the correct ID — no lookup needed.
                    $service->processInternal($record, $data);

                    \Filament\Notifications\Notification::make()
                        ->title('Sukses')
                        ->body('Return internal diproses. Unit siap diambil klien.')
                        ->success()
                        ->send();
                }),

            // 4. REJECT (TOLAK GARANSI)
            Actions\Action::make('reject')
                ->label('Tolak Garansi')
                ->color('danger')
                ->visible(fn (ReturnRequest $record) => in_array($record->status, [
                    ReturnRequest::STATUS_RECEIVED,
                    ReturnRequest::STATUS_SENT_TO_VENDOR,
                ]))
                ->form([
                    Forms\Components\Textarea::make('internal_notes')
                        ->label('Alasan Penolakan')
                        ->placeholder('Contoh: Garansi tidak berlaku, kerusakan tidak termasuk garansi, dsb.')
                        ->required(),
                ])
                ->action(function (ReturnRequest $record, array $data, ReturnService $service) {
                    $service->reject($record, $data['internal_notes'] ?? null);

                    \Filament\Notifications\Notification::make()
                        ->title('RMA Ditolak')
                        ->body("RMA {$record->rma_number} ditolak. SN dikembalikan ke status SOLD.")
                        ->success()
                        ->send();
                }),

            // 5. SELESAI (KEMBALIKAN KE KLIEN)
            Actions\Action::make('return_to_client')
                ->label('Serahkan ke Klien')
                ->color('success')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_READY_FOR_RETURN)
                ->requiresConfirmation()
                ->action(fn (ReturnRequest $record) => $record->update([
                    'status' => ReturnRequest::STATUS_RETURNED_TO_CLIENT,
                    'returned_to_client_date' => now(),
                ])),
        ];
    }

    public function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        $sn = $this->record->serialNumber
            ? $this->record->serialNumber->load(['product', 'warehouse', 'supplier', 'purchaseOrder', 'transactions'])
            : null;

        $stockTransactions = $sn?->transactions ?? collect();

        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Informasi Return')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                TextEntry::make('rma_number')->label('No. RMA'),
                                TextEntry::make('customer.name')->label('Klien'),
                                TextEntry::make('invoice.invoice_number')->label('Invoice'),
                            ]),

                        TextEntry::make('issue_description')
                            ->label('Detail Keluhan')
                            ->prose(),

                        Grid::make(['default' => 1, 'sm' => 4])
                            ->schema([
                                TextEntry::make('warranty_type')
                                    ->label('Rute Garansi')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state === 'supplier' ? 'Garansi Supplier' : 'Garansi Toko'),
                                TextEntry::make('qty')->label('Qty'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ReturnRequest::getStatusLabels()[$state] ?? $state),
                                TextEntry::make('source')->label('Sumber'),
                            ]),
                    ]),

                Section::make('Asal Serial Number')
                    ->description('Tracing asal SN — supplier, PO, inbound/outbound, warehouse')
                    ->schema([
                        TextEntry::make('serial_number')
                            ->label('Serial Number')
                            ->icon('heroicon-o-qr-code'),
                        TextEntry::make('product.name')
                            ->label('Produk'),
                        TextEntry::make('warehouse.name')
                            ->label('Gudang'),
                        TextEntry::make('inbound_date')
                            ->label('Tanggal Inbound')
                            ->date(),
                        TextEntry::make('outbound_date')
                            ->label('Tanggal Outbound')
                            ->date(),
                        TextEntry::make('warranty_expired_at')
                            ->label('Garansi Berakhir')
                            ->date(),
                        TextEntry::make('supplier.name')
                            ->label('Supplier / Distributor'),
                        TextEntry::make('purchaseOrder.purchase_order_number')
                            ->label('No. PO Supplier'),
                    ])
                    ->hidden(! $sn)
                    ->state($sn),

                Section::make('Dokumen Pendukung')
                    ->description('Purchase Order, Purchase Return & financial records linked from this RMA')
                    ->schema([
                        TextEntry::make('procurementClaim.po_number')
                            ->label('No. PO Supplier (Claim)')
                            ->badge()
                            ->color('warning')
                            ->url(fn ($record) => $record->procurementClaim
                                ? route('filament.procurement.resources.purchase-orders.edit', ['record' => $record->procurementClaim->id])
                                : null)
                            ->openInNewTab()
                            ->visible(fn ($record) => $record->procurementClaim),

                        TextEntry::make('purchaseReturn.return_number')
                            ->label('No. Purchase Return')
                            ->badge()
                            ->color('info')
                            ->url(fn ($record) => $record->purchaseReturn
                                ? route('filament.procurement.resources.purchase-returns.view', ['record' => $record->purchaseReturn->id])
                                : null)
                            ->openInNewTab()
                            ->visible(fn ($record) => $record->purchaseReturn),
                    ])
                    ->columnSpanFull(),

                Section::make('Riwayat Stock Transaction (Traceability)')
                    ->description('Audit trail semua gerak stok untuk SN ini, termasuk transaksi RMA ke vendor')
                    ->schema([
                        ListEntry::make('transactions')
                            ->hiddenLabel(true)
                            ->columnSpanFull(),
                    ])
                    ->hidden($stockTransactions->isEmpty())
                    ->state(
                        $stockTransactions->map(fn ($t) => sprintf(
                            '%s — %s — qty: %s — %s',
                            $t->transaction_date?->format('Y-m-d'),
                            $t->mutation_type,
                            $t->quantity,
                            $t->reference_type ? class_basename($t->reference_type) . '#' . ($t->reference_id ?? '-') : '-',
                        ))->toArray()
                    ),
            ]);
    }
}
