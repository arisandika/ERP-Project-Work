<?php

namespace App\Filament\Pages\Project;

use App\Models\Project\Epic;
use App\Models\Project\Project;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;

class EpicsOverview extends Page
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static string $view = 'filament.pages.project.epics-overview';

    protected static ?string $slug = 'pm/epics-overview/{project_id?}';

    protected static string $routePath = 'pm/epics-overview';

    protected static ?string $navigationGroup = 'Manajemen Project';

    protected static ?string $navigationLabel = 'Epics Overview';

    protected static ?string $title = 'Epics Overview';

    protected ?string $subheading = 'Kelola dan pantau epic project beserta ticket dan progresnya';

    protected static ?int $navigationSort = 7;

    public Collection $epics;

    public array $expandedEpics = [];

    public ?int $selectedProjectId = null;

    public Collection $availableProjects;

    public string $searchProject = '';

    public function mount($project_id = null): void
    {
        $this->loadAvailableProjects();

        if ($project_id && $this->availableProjects->contains('id', $project_id)) {
            $this->selectedProjectId = (int) $project_id;
        } elseif ($project_id && !$this->availableProjects->contains('id', $project_id)) {
            Notification::make()
                ->title('Project tidak ditemukan')
                ->body('Project yang dipilih tidak ditemukan atau kamu tidak punya akses ke project ini')
                ->danger()
                ->send();
            $this->redirect(static::getUrl());
        }

        $this->loadEpics();
        $this->expandedEpics = $this->epics->pluck('id')->toArray();
    }

    public function loadAvailableProjects(): void
    {
        $employee = auth()->user()->employee;

        $this->availableProjects = auth()->user()->hasRole('super_admin')
            ? Project::orderByRaw('pinned_date IS NULL')
                ->orderBy('pinned_date', 'desc')
                ->orderBy('name')
                ->get()
            : ($employee ? $employee->projects()
                ->orderByRaw('pinned_date IS NULL')
                ->orderBy('pinned_date', 'desc')
                ->orderBy('name')
                ->get() : collect());
    }

    public function getFilteredProjectsProperty(): Collection
    {
        if (empty($this->searchProject)) {
            return $this->availableProjects;
        }

        return $this->availableProjects->filter(function ($project) {
            return str_contains(strtolower($project->name), strtolower($this->searchProject)) ||
                str_contains(strtolower($project->ticket_prefix ?? ''), strtolower($this->searchProject));
        });
    }

    public function loadEpics(): void
    {
        $query = Epic::with([
            'project',
            'tickets' => function ($query) {
                $query->with(['status', 'assignees', 'creator']);
            },
        ])
            ->orderBy('start_date', 'asc');

        if ($this->selectedProjectId) {
            $query->where('project_id', $this->selectedProjectId);
        }

        $this->epics = $query->get();
    }

    public function updatedSelectedProjectId($value): void
    {
        $this->selectedProjectId = $value ? (int) $value : null;

        if ($this->selectedProjectId) {
            $url = static::getUrl(['project_id' => $this->selectedProjectId]);
            $this->js("Livewire.navigate('{$url}')");
        } else {
            $url = static::getUrl();
            $this->js("Livewire.navigate('{$url}')");
        }

        $this->loadEpics();
        $this->expandedEpics = $this->epics->pluck('id')->toArray();
    }

    public function toggleEpic(int $epicId): void
    {
        if (in_array($epicId, $this->expandedEpics)) {
            $this->expandedEpics = array_diff($this->expandedEpics, [$epicId]);
        } else {
            $this->expandedEpics[] = $epicId;
        }
    }

    public function isExpanded(int $epicId): bool
    {
        return in_array($epicId, $this->expandedEpics);
    }

    public function getEpicStats(Epic $epic): array
    {
        $tickets = $epic->tickets;
        $totalTickets = $tickets->count();

        if ($totalTickets === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'todo' => 0,
                'progress_percentage' => 0,
            ];
        }

        $completed = $tickets->filter(function ($ticket) {
            return in_array($ticket->status?->name, ['Done', 'Completed', 'Closed']);
        })->count();

        $inProgress = $tickets->filter(function ($ticket) {
            return in_array($ticket->status?->name, ['In Progress', 'Review']);
        })->count();

        $todo = $tickets->filter(function ($ticket) {
            return in_array($ticket->status?->name, ['To Do', 'Open', 'New']);
        })->count();

        return [
            'total' => $totalTickets,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'todo' => $todo,
            'progress_percentage' => $totalTickets > 0 ? round(($completed / $totalTickets) * 100) : 0,
        ];
    }

    public function getTicketAssigneesDisplay($ticket): string
    {
        if ($ticket->assignees->isEmpty()) {
            return 'Unassigned';
        }

        $names = $ticket->assignees->pluck('name')->toArray();

        if (count($names) <= 2) {
            return implode(', ', $names);
        }

        return $names[0] . ', ' . $names[1] . ' +' . (count($names) - 2) . ' more';
    }

    #[On('epic-created')]
    #[On('epic-updated')]
    #[On('epic-deleted')]
    #[On('ticket-created')]
    #[On('ticket-updated')]
    #[On('ticket-deleted')]
    public function refreshEpics(): void
    {
        $this->loadEpics();

        $currentEpicIds = $this->epics->pluck('id')->toArray();
        $this->expandedEpics = array_intersect($this->expandedEpics, $currentEpicIds);

        Notification::make()
            ->title('Data berhasil diperbarui')
            ->success()
            ->send();
    }
}
