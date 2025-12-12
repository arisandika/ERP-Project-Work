<?php

namespace App\Filament\Resources\Sales\SalesPersonResource\Pages;

use App\Filament\Resources\Sales\SalesPersonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesPerson extends EditRecord
{
    protected static string $resource = SalesPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['type'] ?? null) === 'internal' && !empty($data['employee_id'])) {
            $emp = \App\Models\HR\Employee::find($data['employee_id']);
            if ($emp) {
                $data['full_name'] = $emp->full_name ?? null;
                $data['email'] = $emp->email ?? null;
                $data['phone'] = $emp->phone_number ?? null;
            }
        }
        return $data;
    }
}
