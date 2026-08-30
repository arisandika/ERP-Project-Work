<?php

namespace App\Filament\Resources\HR\EmployeePerformanceResource\Pages;

use App\Filament\Resources\HR\EmployeePerformanceResource;
use App\Models\HR\Employee;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListEmployeePerformance extends ListRecords
{
    protected static string $resource = EmployeePerformanceResource::class;

    protected static ?string $title = 'Employee Performance';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Karyawan di Bawah Supervizor')
            ->description('Pilih seorang karyawan untuk melihat riwayat project dan kontribusi.')
            ->recordTitleAttribute('full_name')
            ->query(fn() => $this->getSubordinateQuery())
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('position')
                    ->label('Jabatan')
                    ->formatStateUsing(fn(?string $state) => ucwords(str_replace('_', ' ', $state ?? '-')))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('projects_count')
                    ->label('Project')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->label('Task Aktif')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('full_name');
    }

    /**
     * Supervisor sees direct subordinates; super_admin sees all.
     */
    protected function getSubordinateQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return Employee::query();
        }

        $employee = $user->employee;

        if (!$employee) {
            return Employee::where('id', 0); // empty
        }

        return Employee::where('supervisor_id', $employee->id)
            ->withCount(['projects', 'assignedTickets']);
    }
}
