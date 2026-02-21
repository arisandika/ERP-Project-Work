<?php

namespace App\Filament\Resources\HR\ReimbursementRequestResource\Pages;

use App\Filament\Resources\HR\ReimbursementRequestResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateReimbursementRequest extends CreateRecord
{
    protected static string $resource = ReimbursementRequestResource::class;

    public function mount(): void
    {
        parent::mount();

        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses pengajuan reimburse');
        }
    }

    public function getTitle(): string
    {
        return 'Ajukan Reimburse';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        if (!$user?->employee) {
            abort(403, 'User tidak terhubung dengan data karyawan.');
        }

        $employee = $user->employee;

        /**
         * DEFAULT VALUE
         */
        $data['employee_id'] = $employee->id;
        $data['status'] = 'pending';

        return $data;
    }
}
