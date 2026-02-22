<?php

namespace App\Filament\Resources\HR\LeaveApprovalResource\Pages;

use App\Filament\Resources\HR\LeaveApprovalResource;
use App\Filament\Widgets\HR\LeaveApprovalOverview;
use App\Models\HR\LeaveRequest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveApprovals extends ListRecords
{
    protected static string $resource = LeaveApprovalResource::class;

    public function mount(): void
    {
        parent::mount();

        $pendingReq = LeaveRequest::where('status', 'pending')->count();

        if ($pendingReq > 0) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Review Izin Cuti')
                ->body("Terdapat {$pendingReq} permohonan cuti yang menunggu untuk di-review. Silakan cek pada menu Review untuk detailnya.")
                ->persistent()
                ->send();
        }
    }

    public function getTabs(): array
    {
        // Hitung semua badge dalam satu query
        $counts = LeaveRequest::query()
            ->selectRaw("
            COUNT(*) as semua,
            SUM(status = 'pending') as pending,
            SUM(status = 'approved') as approved,
            SUM(status = 'rejected') as rejected
        ")
            ->first();

        return [
            'Semua' => Tab::make()
                ->badge($counts->semua),

            'pending' => Tab::make('Menunggu')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'pending')
                )
                ->badge($counts->pending)
                ->icon('heroicon-o-clock'),

            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'approved')
                )
                ->badge($counts->approved)
                ->icon('heroicon-o-check-badge'),

            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('status', 'rejected')
                )
                ->badge($counts->rejected)
                ->icon('heroicon-o-x-circle'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LeaveApprovalOverview::class,
        ];
    }
}
