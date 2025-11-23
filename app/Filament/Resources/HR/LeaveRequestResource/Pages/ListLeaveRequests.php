<?php
namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use App\Filament\Widgets\HR\LeaveBalancePerType;
use App\Filament\Widgets\HR\LeaveOverview;
use App\Models\HR\LeaveRequest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajukan Cuti'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LeaveOverview::class,
            LeaveBalancePerType::class,
        ];
    }

    public function getTabs(): array
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return [];
        }

        $baseQuery = LeaveRequest::where('employee_id', $employee->id);

        return [
            'semua'    => Tab::make('Semua')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('employee_id', $employee->id)
                )
                ->badge($baseQuery->count()),

            'pending'  => Tab::make('Menunggu')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('employee_id', $employee->id)->where('status', 'pending')
                )
                ->badge($baseQuery->where('status', 'pending')->count()),

            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('employee_id', $employee->id)->where('status', 'approved')
                )
                ->badge($baseQuery->where('status', 'approved')->count()),

            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query->where('employee_id', $employee->id)->where('status', 'rejected')
                )
                ->badge($baseQuery->where('status', 'rejected')->count()),
        ];
    }
}
