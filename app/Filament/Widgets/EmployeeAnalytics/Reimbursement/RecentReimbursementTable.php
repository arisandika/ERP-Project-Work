<?php

namespace App\Filament\Widgets\EmployeeAnalytics\Reimbursement;

use App\Models\Finance\ReimbursementRequest;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;

class RecentReimbursementTable extends TableWidget
{
    protected static ?string $heading = 'Recent Reimbursements';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        $employee = Auth::user()?->employee;

        $startDate = $this->pageFilters['startDate']
            ?? now()->startOfMonth();

        $endDate = $this->pageFilters['endDate']
            ?? now()->endOfMonth();

        return $table
            ->query(
                ReimbursementRequest::query()
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Kategori')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->since()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25]);
    }
}
