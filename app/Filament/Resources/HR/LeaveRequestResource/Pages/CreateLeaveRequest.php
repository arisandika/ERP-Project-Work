<?php

namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        if (! $user?->employee) {
            abort(403, 'Akun ini tidak terhubung dengan data karyawan.');
        }

        // Hitung jumlah hari cuti
        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $data['total_days'] = $start->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
        }, $end) + 1;

        $data['employee_id'] = $user->employee->id;
        $data['status'] = 'pending';

        return $data;
    }
}
