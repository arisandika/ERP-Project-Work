<?php

namespace App\Filament\Pages\Project;

use App\Models\Project\Project;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class ProjectTimeline extends Page
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.project.project-timeline';

    protected static ?string $slug = 'pm/project-timeline';

    protected static string $routePath = 'pm/project-timeline';

    protected static ?string $navigationGroup = 'Manajemen Project';

    protected static ?string $navigationLabel = 'Project Timeline';

    protected static ?string $title = 'Project Timeline';

    protected ?string $subheading = 'Lihat semua project dalam tampilan timeline Gantt';

    protected static ?int $navigationSort = 2;

    public array $counts = [];

    public array $ganttData = ['data' => [], 'links' => []];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->counts = $this->getViewData();
        $this->ganttData = $this->getGanttData();
    }

    public function getProjects()
    {
        $query = Project::query()
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderBy('start_date');

        $employeeIsSuperAdmin = auth()->user()->employee && (
            (method_exists(auth()->user()->employee, 'hasRole') && auth()->user()->employee->hasRole('super_admin'))
            || (isset(auth()->user()->employee->role) && auth()->user()->employee->role === 'super_admin')
        );

        if (!$employeeIsSuperAdmin) {
            $query->whereHas('members', function ($query) {
                $query->where('employee_id', auth()->user()->employee->id);
            });
        }

        return $query->get();
    }

    public function getGanttData(): array
    {
        $projects = $this->getProjects();

        if ($projects->isEmpty()) {
            return ['data' => [], 'links' => []];
        }

        $ganttTasks = [];

        foreach ($projects as $project) {
            $startDate = Carbon::parse($project->start_date);
            $endDate = Carbon::parse($project->end_date);
            $totalDays = $startDate->diffInDays($endDate) + 1;
            $pastDays = min($totalDays, max(0, $startDate->diffInDays(Carbon::now()) + 1));
            $progress = $totalDays > 0 ? min(1.0, $pastDays / $totalDays) : 0;

            // Determine status and color
            $now = Carbon::now();
            $isOverdue = $now->gt($endDate);
            $isNearDeadline = !$isOverdue && $now->diffInDays($endDate) <= 7;
            $isNearlyComplete = $progress >= 0.8;

            if ($isOverdue) {
                $status = 'Overdue';
                $color = '#ef4444';
            } elseif ($isNearlyComplete) {
                $status = 'Nearly Complete';
                $color = '#10b981';
            } elseif ($isNearDeadline) {
                $status = 'Approaching Deadline';
                $color = '#f59e0b';
            } else {
                $status = 'In Progress';
                $color = '#3b82f6';
            }

            $ganttTasks[] = [
                'id' => $project->id,
                'text' => $project->name,
                'start_date' => $startDate->format('d-m-Y H:i'),
                'end_date' => $endDate->format('d-m-Y H:i'),
                'duration' => $totalDays,
                'progress' => $progress,
                'status' => $status,
                'color' => $color,
                'is_overdue' => $isOverdue
            ];
        }

        return [
            'data' => $ganttTasks,
            'links' => []
        ];
    }

    public function getViewData(): array
    {
        $allQuery = Project::query()
            ->whereNotNull('start_date')
            ->whereNotNull('end_date');

        // Apply role-based filtering
        $employeeIsSuperAdmin = auth()->user()->employee && (
            (method_exists(auth()->user()->employee, 'hasRole') && auth()->user()->employee->hasRole('super_admin'))
            || (isset(auth()->user()->employee->role) && auth()->user()->employee->role === 'super_admin')
        );

        if (!$employeeIsSuperAdmin) {
            $allQuery->whereHas('members', function ($query) {
                $query->where('employee_id', auth()->user()->employee->id);
            });
        }

        return [
            'all' => $allQuery->count(),
        ];
    }
}