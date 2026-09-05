<?php

namespace App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;

use App\Filament\Resources\AfterSales\ReturnRequestResource;
use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use App\Services\ReturnService;
use App\Services\AfterSales\ReturnWorkflowService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewReturnRequest extends ViewRecord
{
    protected static string $resource = ReturnRequestResource::class;

    /**
     * Actions staged mengikuti business flow RMA:
     *   SUBMITTED → UNDER_REVIEW → APPROVED / REJECTED
     *   → WAITING_FOR_RETURN → RECEIVED
     *   → inspection + warranty decision → resolution routing (internal / vendor / refund)
     *   → READY_FOR_RETURN → RETURNED_TO_CLIENT
     *
     * Refinement:
     *   - request_shipment (APPROVED → WAITING_FOR_RETURN): status menunggu pengiriman
     *     dipakai eksplisit, approve ≠ terima barang.
     *   - record_inspection: no_fault_found → WARRANTY_NA (bukan approved).
     *   - set_resolution: route ke entity InternalRepair / VendorClaim + refund_status=pending.
     *   - confirm_refund: refund selesai Finance → READY_FOR_RETURN.
     */
    protected function getHeaderActions(): array
    {
        return [
            // 1. REVIEW (submitted -> under_review)
            Actions\Action::make('start_review')
                ->label('Mulai Review')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_SUBMITTED)
                ->form([
                    Forms\Components\Textarea::make('review_notes')
                        ->label('Catatan Review')
                        ->rows(3)
                        ->placeholder('Cek kelengkapan data, validitas customer, garansi, bukti, dll.'),
                ])
                ->action(function (ReturnRequest $record, array $data) {
                    $record->update([
                        'status'         => ReturnRequest::STATUS_UNDER_REVIEW,
                        'internal_notes' => $data['review_notes']
                            ? ($record->internal_notes ?? '') . "\n[REVIEW] " . $data['review_notes']
                            : $record->internal_notes,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Review Dimulai')
                        ->body("RMA {$record->rma_number} masuk review.")
                        ->success()->send();
                }),

            // 2. APPROVE (under_review -> approved) — pengajuan diterima, barang belum tentu dikirim
            Actions\Action::make('approve_request')
                ->label('Setujui Pengajuan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_UNDER_REVIEW)
                ->requiresConfirmation()
                ->modalHeading('Setujui Pengajuan RMA')
                ->modalDescription('Pengajuan diterima untuk dilanjutkan. Customer akan diminta mengirim barang.')
                ->action(function (ReturnRequest $record) {
                    $record->update([
                        'status'            => ReturnRequest::STATUS_APPROVED,
                        'warranty_decision' => ReturnRequest::WARRANTY_PENDING,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Pengajuan Disetujui')
                        ->body("RMA {$record->rma_number} disetujui. Menunggu barang dikirim customer.")
                        ->success()->send();
                }),

            // 3. REJECT (submitted/under_review -> rejected) — tolak pengajuan di level review
            Actions\Action::make('reject_request')
                ->label('Tolak Pengajuan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (ReturnRequest $record) => in_array($record->status, [
                    ReturnRequest::STATUS_SUBMITTED,
                    ReturnRequest::STATUS_UNDER_REVIEW,
                ]))
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Penolakan')
                        ->placeholder('Contoh: bukan member, invoice tidak valid, dll.')
                        ->required(),
                ])
                ->action(function (ReturnRequest $record, array $data, ReturnService $service) {
                    $service->reject($record, "[REVIEW] " . $data['reason']);

                    \Filament\Notifications\Notification::make()
                        ->title('Pengajuan Ditolak')
                        ->body("RMA {$record->rma_number} ditolak.")
                        ->success()->send();
                }),

            // 4. MINTA PENGIRIMAN (approved -> waiting_for_return)
            //    Customer diminta kirim barang. Status ini menandakan belum ada fisik barang masuk.
            Actions\Action::make('request_shipment')
                ->label('Minta Pengiriman Barang')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_APPROVED)
                ->requiresConfirmation()
                ->modalHeading('Minta Pengiriman Barang')
                ->modalDescription('Customer akan diminta mengirim unit return ke toko / service center.')
                ->action(function (ReturnRequest $record) {
                    $record->update([
                        'status' => ReturnRequest::STATUS_WAITING_FOR_RETURN,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Menunggu Pengiriman')
                        ->body("RMA {$record->rma_number} menunggu barang dikirim customer.")
                        ->success()->send();
                }),

            // 5. BARANG MASUK (waiting_for_return -> received) — fisik barang diterima
            Actions\Action::make('receive_from_client')
                ->label('Barang Masuk')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_WAITING_FOR_RETURN)
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Barang Masuk')
                ->modalDescription('Konfirmasi bahwa barang return sudah benar-benar diterima di toko / service center dari klien.')
                ->action(function (ReturnRequest $record) {
                    $record->update([
                        'status'            => ReturnRequest::STATUS_RECEIVED,
                        'received_date'     => now(),
                        'inspection_result' => null,
                        'warranty_decision' => ReturnRequest::WARRANTY_PENDING,
                        'resolution_type'   => null,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Barang Diterima')
                        ->body("RMA {$record->rma_number} diterima. Masuk antrean pemeriksaan.")
                        ->success()->send();
                }),

            // 6. HASIL INSPEKSI + KEPUTUSAN GARANSI (received)
            Actions\Action::make('record_inspection')
                ->label('Hasil Inspeksi & Garansi')
                ->icon('heroicon-o-magnifying-glass')
                ->color('warning')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_RECEIVED)
                ->form([
                    Forms\Components\Radio::make('inspection_result')
                        ->label('Hasil Pemeriksaan')
                        ->options(ReturnRequest::getInspectionResultLabels())
                        ->inline()->live()->required(),
                    Forms\Components\Radio::make('warranty_decision')
                        ->label('Keputusan Garansi')
                        ->options([
                            ReturnRequest::WARRANTY_APPROVED => 'Disetujui',
                            ReturnRequest::WARRANTY_REJECTED => 'Ditolak',
                            ReturnRequest::WARRANTY_NA       => 'Garansi Tidak Berlaku',
                        ])
                        ->inline()->required()
                        ->visible(fn (Forms\Get $get) => $get('inspection_result') !== ReturnRequest::INSPECTION_NO_FAULT_FOUND),
                    Forms\Components\Textarea::make('internal_notes')
                        ->label('Catatan Teknisi / Admin')
                        ->rows(3),
                ])
                ->action(function (ReturnRequest $record, array $data) {
                    // tidak ditemukan kerusakan → WARRANTY_NA (bukan klaim garansi)
                    if (($data['inspection_result'] ?? null) === ReturnRequest::INSPECTION_NO_FAULT_FOUND) {
                        $data['warranty_decision'] = ReturnRequest::WARRANTY_NA;
                    }

                    // Jika garansi ditolak, RMA langsung close sebagai warranty_rejected (terminal).
                    if (($data['warranty_decision'] ?? null) === ReturnRequest::WARRANTY_REJECTED) {
                        $record->update([
                            'inspection_result' => $data['inspection_result'] ?? null,
                            'warranty_decision' => ReturnRequest::WARRANTY_REJECTED,
                            'internal_notes'    => $data['internal_notes'] ?? null,
                            'status'            => ReturnRequest::STATUS_WARRANTY_REJECTED,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Garansi Ditolak')
                            ->body("RMA {$record->rma_number} ditolak garansinya.")
                            ->success()->send();
                        return;
                    }

                    $record->update([
                        'inspection_result' => $data['inspection_result'] ?? null,
                        'warranty_decision' => $data['warranty_decision'] ?? ReturnRequest::WARRANTY_APPROVED,
                        'internal_notes'    => $data['internal_notes'] ?? null,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Inspeksi Tercatat')
                        ->body('Hasil pemeriksaan & keputusan garansi disimpan.')
                        ->success()->send();
                }),

            // 7. TENTUKAN PENYELESAIAN + ROUTING (received, warranty approved/na)
            //    Route ke entity InternalRepair / VendorClaim / refund.
            Actions\Action::make('set_resolution')
                ->label('Tentukan Penyelesaian')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_RECEIVED
                    && in_array($record->warranty_decision, [
                        ReturnRequest::WARRANTY_APPROVED,
                        ReturnRequest::WARRANTY_NA,
                    ], true))
                ->form([
                    Forms\Components\Radio::make('resolution_type')
                        ->label('Jenis Penyelesaian')
                        ->options(ReturnRequest::getResolutionTypeLabels())
                        ->inline()->live()->required(),
                    Forms\Components\Select::make('new_serial_number_id')
                        ->label('SN Unit Pengganti')
                        ->options(fn (ReturnRequest $record) => SerialNumber::where('product_id', $record->serialNumber?->product_id ?? $record->product_id)
                            ->where('status', SerialNumber::STATUS_AVAILABLE)
                            ->pluck('serial_number', 'id'))
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('resolution_type') === ReturnRequest::RESOLUTION_REPLACEMENT)
                        ->required(fn (Forms\Get $get) => $get('resolution_type') === ReturnRequest::RESOLUTION_REPLACEMENT),
                ])
                ->action(function (ReturnRequest $record, array $data) {
                    $resolution = $data['resolution_type'];

                    // SEBAGIAN PENYELESAIAN DIPINDAH KE SERVICE UNTUK ENTITY SEPARATE.

                    // NO_FAULT_FOUND: resolve tanpa repair — langsung ready_for_return.
                    if ($resolution === ReturnRequest::RESOLUTION_NO_FAULT_FOUND) {
                        $record->update([
                            'resolution_type' => ReturnRequest::RESOLUTION_NO_FAULT_FOUND,
                            'status'          => ReturnRequest::STATUS_READY_FOR_RETURN,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Selesai')
                            ->body('Tidak ditemukan kerusakan. Unit dikembalikan ke klien.')
                            ->success()->send();
                        return;
                    }

                    // REFUND: proses refund finance → refund_status=pending, RMA=REFUND_PENDING.
                    if ($resolution === ReturnRequest::RESOLUTION_REFUND) {
                        app(ReturnService::class)->processRefund($record);
                        $record->update([
                            'status' => ReturnRequest::STATUS_REFUND_PENDING,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Refund Diproses')
                            ->body('Refund dicatat ke modul Finance. Tunggu konfirmasi Finance untuk menutup RMA.')
                            ->success()->send();
                        return;
                    }

                    // REPAIR_AND_RETURN / REPLACEMENT: simpan resolusi + route.
                    $record->update([
                        'resolution_type'       => $resolution,
                        'new_serial_number_id'  => $data['new_serial_number_id'] ?? null,
                    ]);

                    if ($record->warranty_type === 'supplier') {
                        // JALUR VENDOR: create PO claim + VendorClaim, status SENT_TO_VENDOR.
                        app(ReturnService::class)->sendToVendor($record, now());
                        \Filament\Notifications\Notification::make()
                            ->title('Rute Vendor')
                            ->body("RMA {$record->rma_number} terkirim ke supplier (klaim vendor terbentuk).")
                            ->success()->send();
                        return;
                    }

                    // JALUR INTERNAL (store): mulai servis + buat InternalRepair row.
                    app(ReturnWorkflowService::class)->startInternalRepair($record);
                    \Filament\Notifications\Notification::make()
                        ->title('Rute Internal')
                        ->body("RMA {$record->rma_number} masuk antrean Servis Internal.")
                        ->success()->send();
                }),

            // 8. KONFIRMASI REFUND (refund_pending -> finance selesai -> ready_for_return)
            Actions\Action::make('confirm_refund')
                ->label('Konfirmasi Refund Finance')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_REFUND_PENDING
                    && $record->refund_status === ReturnRequest::REFUND_PENDING)
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Refund Selesai')
                ->modalDescription('Finance sudah menyelesaikan refund. RMA siap ditutup.')
                ->action(function (ReturnRequest $record) {
                    app(ReturnWorkflowService::class)->markRefundCompleted($record);

                    \Filament\Notifications\Notification::make()
                        ->title('Refund Selesai')
                        ->body("RMA {$record->rma_number} refund selesai. Siap dikembalikan ke klien.")
                        ->success()->send();
                }),

            // 9. KEMBALIKAN KE KLIEN (ready_for_return -> returned_to_client) — penutupan
            Actions\Action::make('return_to_client')
                ->label('Kembalikan ke Klien')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_READY_FOR_RETURN)
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pengembalian')
                ->modalDescription('Konfirmasi bahwa unit (perbaikan / pengganti / refund) sudah diserahkan kembali ke klien.')
                ->action(function (ReturnRequest $record) {
                    $record->update([
                        'status'                  => ReturnRequest::STATUS_RETURNED_TO_CLIENT,
                        'returned_to_client_date' => now(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Dikembalikan')
                        ->body("RMA {$record->rma_number} selesai. Unit dikembalikan ke klien.")
                        ->success()->send();
                }),
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

                        TextEntry::make('issue_type')
                            ->label('Jenis Kendala')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ReturnRequest::getIssueTypeLabels()[$state] ?? $state),

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

                Section::make('Hasil Pemeriksaan & Garansi')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])
                            ->schema([
                                TextEntry::make('inspection_result')
                                    ->label('Hasil Inspeksi')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ReturnRequest::getInspectionResultLabels()[$state] ?? $state),
                                TextEntry::make('warranty_decision')
                                    ->label('Keputusan Garansi')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ReturnRequest::getWarrantyDecisionLabels()[$state] ?? $state),
                                TextEntry::make('resolution_type')
                                    ->label('Penyelesaian')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ReturnRequest::getResolutionTypeLabels()[$state] ?? $state),
                            ]),
                    ]),

                Section::make('Refund')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                TextEntry::make('refund_status')
                                    ->label('Status Refund')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        ReturnRequest::REFUND_PENDING   => 'Menunggu Finance',
                                        ReturnRequest::REFUND_COMPLETED => 'Selesai',
                                        default                        => '—',
                                    })
                                    ->color(fn ($state) => match ($state) {
                                        ReturnRequest::REFUND_PENDING   => 'warning',
                                        ReturnRequest::REFUND_COMPLETED => 'success',
                                        default                        => 'gray',
                                    }),
                            ]),
                    ])
                    ->visible(fn (ReturnRequest $record) => $record->resolution_type === ReturnRequest::RESOLUTION_REFUND),

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
                                : null, shouldOpenInNewTab: true)
                            ->visible(fn ($record) => $record->procurementClaim),

                        TextEntry::make('purchaseReturn.return_number')
                            ->label('No. Purchase Return')
                            ->badge()
                            ->color('info')
                            ->url(fn ($record) => $record->purchaseReturn
                                ? route('filament.procurement.resources.purchase-returns.view', ['record' => $record->purchaseReturn->id])
                                : null, shouldOpenInNewTab: true)
                            ->visible(fn ($record) => $record->purchaseReturn),
                    ])
                    ->columnSpanFull(),

                Section::make('Riwayat Stock Transaction (Traceability)')
                    ->description('Audit trail semua gerak stok untuk SN ini, termasuk transaksi RMA ke vendor')
                    ->schema([
                        TextEntry::make('transactions')
                            ->hiddenLabel(true)
                            ->listWithLineBreaks()
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