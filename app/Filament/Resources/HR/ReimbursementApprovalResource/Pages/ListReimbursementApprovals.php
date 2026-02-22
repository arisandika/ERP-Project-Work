<?php

namespace App\Filament\Resources\HR\ReimbursementApprovalResource\Pages;

use App\Filament\Resources\HR\ReimbursementApprovalResource;
use App\Filament\Widgets\HR\ReimburseApprovalOverview;
use App\Models\HR\ReimbursementRequest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListReimbursementApprovals extends ListRecords
{
    protected static string $resource = ReimbursementApprovalResource::class;

    public function mount(): void
    {
        parent::mount();

        $pendingReq = ReimbursementRequest::where('status', 'pending')->count();

        if ($pendingReq > 0) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Review Reimburse')
                ->body("Terdapat {$pendingReq} permohonan reimburse yang menunggu untuk di-review. Silakan cek pada menu Review untuk detailnya.")
                ->persistent()
                ->send();
        }
    }

    public function getTabs(): array
    {
        // Ambil semua count dalam 1 query
        $counts = ReimbursementRequest::query()
            ->selectRaw("
            COUNT(*) as semua,
            SUM(status = 'pending') as pending,
            SUM(status = 'approved') as approved,
            SUM(status = 'rejected') as rejected
        ")
            ->first();

        return [
            'semua' => Tab::make('Semua')
                ->modifyQueryUsing(fn(Builder $query) => $query)
                ->badge($counts->semua),

            'pending' => Tab::make('Menunggu')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending'))
                ->badge($counts->pending)
                ->icon('heroicon-o-clock'),

            'approved' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'approved'))
                ->badge($counts->approved)
                ->icon('heroicon-o-check-badge'),

            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'rejected'))
                ->badge($counts->rejected)
                ->icon('heroicon-o-x-circle'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ReimburseApprovalOverview::class,
        ];
    }
}
