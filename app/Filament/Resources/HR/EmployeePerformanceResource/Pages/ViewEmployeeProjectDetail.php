<?php

namespace App\Filament\Resources\HR\EmployeePerformanceResource\Pages;

use App\Filament\Resources\HR\EmployeePerformanceResource;
use App\Models\HR\Employee;
use App\Models\HR\PerformanceEvaluation;
use App\Models\Project\Project;
use App\Models\Project\Ticket;
use App\Models\Project\TicketStatus;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ViewEmployeeProjectDetail extends Page
{
    protected static string $resource = EmployeePerformanceResource::class;

    protected static string $view = 'filament.pages.hr.employee-project-detail';

    protected static string $routePath = 'hr.employees.performance.project';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?string $title = 'Detail Project Karyawan';

    protected ?string $subheading = 'Lihat kontribusi dan task karyawan pada project ini.';

    public Employee $employee;

    public Project $project;

    public ?string $role = null;

    /** @var \Illuminate\Support\Collection */
    public $tasks;

    /** @var \Illuminate\Support\Collection */
    public $evaluations;

    public function mount(int $record, int $project): void
    {
        $user = auth()->user();

        // Access control: supervisor of the employee or super_admin
        $this->employee = Employee::with(['department', 'office', 'supervisor'])->findOrFail($record);

        abort_if(
            !$user->hasRole('super_admin')
            && !($user->employee && $user->employee->can('viewMetrics', $this->employee)),
            403
        );

        $this->project = Project::with(['ticketStatuses', 'projectManager'])->findOrFail($project);

        // Verify the employee is actually a member of this project
        $membership = $this->employee->projects()->where('project_id', $project)->first();
        abort_if(!$membership, 404, 'Employee is not a member of this project.');

        $this->role = $membership->pivot->role;

        $this->loadTasks();
        $this->loadEvaluations();
    }

    public function loadTasks(): void
    {
        $today = Carbon::today();
        $completedStatusIds = TicketStatus::where('project_id', $this->project->id)
            ->where('is_completed', true)
            ->pluck('id');

        $tickets = Ticket::where('project_id', $this->project->id)
            ->where(function ($q) {
                $q->whereHas('assignees', fn($sub) => $sub->where('employee_id', $this->employee->id))
                    ->orWhere('created_by', $this->employee->id);
            })
            ->with(['status', 'priority', 'epic', 'assignees', 'creator'])
            ->orderBy('due_date', 'asc')
            ->get();

        $this->tasks = $tickets->map(function (Ticket $ticket) use ($completedStatusIds, $today) {
            $isCompleted = $completedStatusIds->contains($ticket->ticket_status_id);
            $isOverdue = !$isCompleted && $ticket->due_date && $today->gt(Carbon::parse($ticket->due_date));

            return (object) [
                'id' => $ticket->id,
                'uuid' => $ticket->uuid,
                'name' => $ticket->name,
                'epic' => $ticket->epic?->name,
                'priority' => $ticket->priority?->name,
                'status' => $ticket->status?->name,
                'status_color' => $ticket->status?->color,
                'due_date' => $ticket->due_date?->format('d M Y'),
                'is_completed' => $isCompleted,
                'is_overdue' => $isOverdue,
                'creator' => $ticket->creator?->full_name,
            ];
        });
    }

    public function loadEvaluations(): void
    {
        $this->evaluations = PerformanceEvaluation::where('employee_id', $this->employee->id)
            ->with('evaluator')
            ->orderByDesc('evaluated_at')
            ->get()
            ->map(fn(PerformanceEvaluation $e) => (object) [
                'period' => $e->period,
                'rating' => $e->rating,
                'rating_label' => PerformanceEvaluation::RATINGS[$e->rating] ?? '-',
                'feedback' => $e->feedback,
                'evaluated_at' => $e->evaluated_at?->format('d M Y'),
                'evaluator' => $e->evaluator?->full_name,
            ]);
    }

    public function getTotalTasks(): int
    {
        return $this->tasks->count();
    }

    public function getCompletedTasks(): int
    {
        return $this->tasks->where('is_completed', true)->count();
    }

    public function getOverdueTasks(): int
    {
        return $this->tasks->where('is_overdue', true)->count();
    }

    public function getCompletionRate(): float
    {
        $total = $this->getTotalTasks();

        return $total > 0 ? round(($this->getCompletedTasks() / $total) * 100, 1) : 0.0;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_evaluation')
                ->label('Tambah Penilaian')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form($this->getEvaluationFormSchema())
                ->action(fn(array $data) => $this->createEvaluation($data)),
            Action::make('back_to_employee')
                ->label('Kembali ke Profil')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(EmployeePerformanceResource::getUrl('view', ['record' => $this->employee->id])),
        ];
    }

    protected function getEvaluationFormSchema(): array
    {
        $opts = [
            5 => '5 - Outstanding',
            4 => '4 - Good',
            3 => '3 - Average',
            2 => '2 - Poor',
            1 => '1 - Very Poor',
        ];

        return [
            Select::make('evaluator_id')
                ->label('Penilai')
                ->options(Employee::pluck('full_name', 'id'))
                ->default(auth()->user()->employee?->id)
                ->required()
                ->searchable()
                ->preload(),
            TextInput::make('period')
                ->label('Periode')
                ->required()
                ->placeholder('cth: 2025-Q4, 2025-12')
                ->maxLength(32),
            Radio::make('rating')
                ->label('Overall Rating')
                ->required()
                ->options($opts)
                ->inline()
                ->default(3),
            Radio::make('quality')
                ->label('Quality of Work')
                ->options($opts)
                ->inline()
                ->default(3),
            Radio::make('teamwork')
                ->label('Teamwork')
                ->options($opts)
                ->inline()
                ->default(3),
            Radio::make('communication')
                ->label('Communication')
                ->options($opts)
                ->inline()
                ->default(3),
            Radio::make('problem_solving')
                ->label('Problem Solving')
                ->options($opts)
                ->inline()
                ->default(3),
            DatePicker::make('evaluated_at')
                ->label('Tanggal Penilaian')
                ->default(now())
                ->required()
                ->displayFormat('d M Y')
                ->native(false),
            Textarea::make('feedback')
                ->label('Comments / Notes')
                ->maxLength(2000)
                ->rows(3),
        ];
    }

    public function createEvaluation(array $data): void
    {
        PerformanceEvaluation::create([
            'employee_id' => $this->employee->id,
            'evaluator_id' => $data['evaluator_id'],
            'period' => $data['period'],
            'rating' => $data['rating'],
            'quality' => $data['quality'] ?? null,
            'teamwork' => $data['teamwork'] ?? null,
            'communication' => $data['communication'] ?? null,
            'problem_solving' => $data['problem_solving'] ?? null,
            'feedback' => $data['feedback'] ?? null,
            'evaluated_at' => $data['evaluated_at'],
        ]);

        $this->loadEvaluations();

        Notification::make()
            ->success()
            ->title('Penilaian berhasil disimpan')
            ->send();
    }
}
