<?php

namespace App\Filament\Resources\HR\SickApprovalResource\Pages;

use App\Filament\Resources\HR\SickApprovalResource;
use App\Filament\Widgets\HR\SickApprovalOverview;
use App\Models\HR\SickRequest;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSickApprovals extends ListRecords
{
    protected static string $resource = SickApprovalResource::class;

    public function mount(): void
    {
        parent::mount();

        $pendingReq = SickRequest::where('status', 'pending')->count();

        if ($pendingReq > 0) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Review Izin Sakit')
                ->body("Terdapat {$pendingReq} permohonan sakit yang menunggu untuk di-review. Silakan cek pada menu Review untuk detailnya.")
                ->persistent()
                ->send();
        }
    }

    public function getTabs(): array
    {
        // Hitung semua badge dalam satu query
        $counts = SickRequest::query()
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
            SickApprovalOverview::class,
        ];
    }
}