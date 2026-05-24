<?php

namespace App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;

use App\Filament\Resources\AfterSales\ReturnRequestResource;
use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use App\Services\ReturnService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;

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
                ->action(fn (ReturnRequest $record, array $data) => $record->update([
                    'status' => ReturnRequest::STATUS_SENT_TO_VENDOR,
                    'sent_to_vendor_date' => $data['sent_to_vendor_date'],
                ])),

            // 2. TERIMA DARI VENDOR
            Actions\Action::make('receive_from_vendor')
                ->label('Terima dari Vendor')
                ->color('info')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_SENT_TO_VENDOR)
                ->form([
                    Forms\Components\Radio::make('resolution_type')
                        ->options(['repaired' => 'Repair (SN Tetap)', 'replaced' => 'Replace (SN Baru)'])
                        ->inline()->live()->required(),
                    Forms\Components\TextInput::make('new_serial_number')
                        ->visible(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                        ->required(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                        ->unique('nx_serial_number', 'serial_number'),
                    Forms\Components\Textarea::make('vendor_notes')->required(),
                ])
                ->action(function (ReturnRequest $record, array $data, ReturnService $service) {
                    $service->receiveFromVendor($record, $data);
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
                            ->where('status', 'available') // Sesuaikan status SN Anda
                            ->pluck('serial_number', 'id')
                        )
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                        ->required(fn (Forms\Get $get) => $get('resolution_type') === 'replaced'),
                    Forms\Components\Textarea::make('internal_notes')->required(),
                ])
                ->action(function (ReturnRequest $record, array $data, ReturnService $service) {
                    $service->processInternal($record, $data);
                }),

            // 4. SELESAI (KEMBALIKAN KE KLIEN)
            Actions\Action::make('return_to_client')
                ->label('Serahkan ke Klien')
                ->color('success')
                ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_READY_FOR_RETURN)
                ->requiresConfiReturntion()
                ->action(fn (ReturnRequest $record) => $record->update([
                    'status' => ReturnRequest::STATUS_RETURNED_TO_CLIENT,
                    'returned_to_client_date' => now(),
                ])),
        ];
    }
}
