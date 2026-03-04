<?php
namespace App\Filament\Resources\HR\LeaveRequestResource\Pages;

use App\Filament\Resources\HR\LeaveRequestResource;
use App\Filament\Widgets\HR\LeaveBalancePerType;
use App\Filament\Widgets\HR\LeaveRequestOverview;
use App\Models\HR\LeaveRequest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    public function mount(): void
    {
        parent::mount();
        
        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses pengajuan cuti');
        }
    }

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
            LeaveRequestOverview::class,
            LeaveBalancePerType::class,
        ];
    }

    public function getTabs(): array
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return [];
        }

        // Hitung semua badge dalam satu query
        $counts = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->selectRaw("
            COUNT(*) as semua,
            SUM(status = 'pending') as pending,
            SUM(status = 'approved') as approved,
            SUM(status = 'rejected') as rejected
        ")
            ->first();

        return [
            'semua' => Tab::make('Semua')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('employee_id', $employee->id)
                )
                ->badge($counts->semua),

            'pending' => Tab::make('Menunggu')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('employee_id', $employee->id)
                        ->where('status', 'pending')
                )
                ->badge($counts->pending)
                ->icon('heroicon-o-clock'),

            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('employee_id', $employee->id)
                        ->where('status', 'approved')
                )
                ->badge($counts->approved)
                ->icon('heroicon-o-check-badge'),

            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('employee_id', $employee->id)
                        ->where('status', 'rejected')
                )
                ->badge($counts->rejected)
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
