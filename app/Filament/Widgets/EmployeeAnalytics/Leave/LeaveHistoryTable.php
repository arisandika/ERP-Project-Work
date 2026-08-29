<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Leave;

use App\Models\HR\LeaveRequest;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeaveHistoryTable extends TableWidget
{
    protected static ?string $heading = 'Leave History';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        $employee = Auth::user()?->employee;

        $startDate = $this->pageFilters['startDate'] ?? now()->startOfMonth();
        $endDate = $this->pageFilters['endDate'] ?? now()->endOfMonth();

        return $table
            ->query(
                LeaveRequest::query()
                    ->with('leave')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('leave.leave_type')
                    ->label('Leave Type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_days')
                    ->label('Days')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested At')
                    ->since(),
            ])
            ->paginated([5, 10, 25]);
    }
}
