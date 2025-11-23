<?php

namespace App\Filament\Resources\HR\LeaveApprovalResource\Pages;

use App\Filament\Resources\HR\LeaveApprovalResource;
use App\Models\HR\LeaveRequest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveApprovals extends ListRecords
{
    protected static string $resource = LeaveApprovalResource::class;

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make(),

            'pending'  => Tab::make('Menunggu')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(LeaveRequest::where('status', 'pending')->count())
                ->icon('heroicon-o-clock'),

            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(LeaveRequest::where('status', 'approved')->count())
                ->icon('heroicon-o-check-badge'),

            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(LeaveRequest::where('status', 'rejected')->count())
                ->icon('heroicon-o-x-circle'),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $pendingReq = LeaveRequest::where('status', 'pending')->count();

        if ($pendingReq > 0) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Review Izin Cuti')
                ->body("Terdapat {$pendingReq} permohonan cuti yang menunggu untuk di-review. Silakan cek pada menu untuk detailnya.")
                ->persistent()
                ->send();
        }
    }
}
