<?php

namespace App\Filament\Resources\Sales\DeliveryOrderResource\Pages;

use App\Filament\Resources\Sales\DeliveryOrderResource;
use App\Models\Sales\DeliveryOrder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class EditDeliveryOrder extends EditRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit Surat Jalan';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
