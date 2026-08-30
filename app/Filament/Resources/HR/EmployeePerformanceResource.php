<?php

namespace App\Filament\Resources\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\HR\EmployeePerformanceResource\Pages\ListEmployeePerformance;
use App\Filament\Resources\HR\EmployeePerformanceResource\Pages\ViewEmployeePerformance;
use App\Filament\Resources\HR\EmployeePerformanceResource\Pages\ViewEmployeeProjectDetail;
use App\Models\HR\Employee;
use Filament\Resources\Resource;

/**
 * Employee Performance resource (read-only).
 *
 * Exposes project/task contribution metrics and performance-evaluation
 * workflows for employees reporting to the logged-in supervisor.
 *
 * Uses Employee as the backing model for list/view routing; no create/edit
 * of employee records is offered here.
 */
class EmployeePerformanceResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'hr';
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Manajemen HR';
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'hr/employees/performance';
    protected static ?string $pluralModelLabel = 'Employee Performance';

    public static function getNavigationBadge(): ?string
    {
        $employee = auth()->user()?->employee;

        if (!$employee || auth()->user()->hasRole('super_admin')) {
            return null;
        }

        return (string) $employee->subordinates()->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeePerformance::route('/'),
            'view'  => ViewEmployeePerformance::route('/{record}'),
            'project' => ViewEmployeeProjectDetail::route('/{record}/project/{project}'),
        ];
    }
}
