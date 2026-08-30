<?php

namespace App\Filament\Resources\HR\EmployeePerformanceResource\Pages;

use App\Filament\Resources\HR\EmployeePerformanceResource;
use App\Filament\Resources\HR\EmployeePerformanceResource\RelationManagers\PerformanceEvaluationsRelationManager;
use App\Filament\Resources\HR\EmployeePerformanceResource\RelationManagers\ProjectsRelationManager;
use App\Models\HR\Employee;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Concerns\HasRelationManagers;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeePerformance extends ViewRecord
{
    use HasRelationManagers;

    protected static string $resource = EmployeePerformanceResource::class;

    protected static ?string $title = 'Employee Performance';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(EmployeePerformanceResource::getUrl('index')),
        ];
    }

    /**
     * Ensure only supervisor/subordinates or self can view.
     */
    public function getRecord(): Employee
    {
        $record = parent::getRecord();

        $user = $this->getUser();

        // super_admin bypass
        if ($user->hasRole('super_admin')) {
            return $record;
        }

        $employee = $user->employee;

        if ($employee && $employee->can('viewMetrics', $record)) {
            return $record;
        }

        abort(403);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Karyawan')
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('Nama Lengkap')
                            ->weight('semibold')
                            ->size('lg')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->color('warning'),
                        TextEntry::make('position')
                            ->label('Jabatan')
                            ->formatStateUsing(fn(?string $state) => ucwords(str_replace('_', ' ', $state ?? '-')))
                            ->placeholder('—'),
                        TextEntry::make('department.name')
                            ->label('Departemen')
                            ->placeholder('—'),
                        TextEntry::make('join_date')
                            ->label('Tanggal Masuk')
                            ->date('d M Y')
                            ->placeholder('—'),
                        TextEntry::make('supervisor.full_name')
                            ->label('Supervisor')
                            ->placeholder('—')
                            ->visible(fn(Employee $record) => $record->supervisor_id !== null),
                    ])
                    ->columns(['default' => 122, 'md' => 2]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProjectsRelationManager::class,
            PerformanceEvaluationsRelationManager::class,
        ];
    }
}
