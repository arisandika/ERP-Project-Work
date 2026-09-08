<?php
namespace App\Filament\Resources\SalesActivity\VisitAssignmentResource\Pages;

use App\Filament\Resources\SalesActivity\VisitAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitAssignment extends EditRecord
{
    protected static string $resource = VisitAssignmentResource::class;

    public function getTitle(): string
    {
        return 'Edit Kunjungan';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}