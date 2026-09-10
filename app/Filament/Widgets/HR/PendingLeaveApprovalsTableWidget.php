<?php
namespace App\Filament\Widgets\HR;

use App\Filament\Resources\HR\LeaveApprovalResource;
use App\Models\HR\LeaveRequest;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class PendingLeaveApprovalsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Cuti Menunggu Persetujuan';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeaveRequest::query()
                    ->where('status', 'pending')
                    ->orderBy('start_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Nama Karyawan')
                    ->weight('semibold')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('employee.department.name')
                    ->label('Departemen')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('leave.leave_type')
                    ->label('Jenis Cuti')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('total_days')
                    ->label('Durasi')
                    ->formatStateUsing(fn($state) => $state . ' Hari')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->since()
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->url(fn(LeaveRequest $record) => LeaveApprovalResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('Tidak ada cuti yang menunggu persetujuan')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
