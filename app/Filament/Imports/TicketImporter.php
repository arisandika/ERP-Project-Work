<?php

namespace App\Filament\Imports;

use App\Models\Project\Ticket;
use App\Models\Project\TicketStatus;
use App\Models\Project\TicketPriorities;
use App\Models\Project\Epic;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Importer;

class TicketImporter extends Importer
{
    protected static ?string $model = Ticket::class;

    /**
     * Provide notification body when import is completed.
     */
    public function getCompletedNotificationBody(\Filament\Actions\Imports\Models\Import $import): string
    {
        return 'Ticket import completed successfully.';
    }

    /**
     * Kolom harus match header Excel
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->fillRecordUsing(fn (Ticket $record, $state) => $record->name = $state),

            ImportColumn::make('description')
                ->rules(['nullable', 'string']),

            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->castStateUsing(fn (string $state) => trim($state)),

            ImportColumn::make('priority')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->castStateUsing(fn (string $state) => trim($state)),

            ImportColumn::make('epic')
                ->rules(['nullable', 'string'])
                ->castStateUsing(fn ($state) => blank($state) ? null : trim($state)),

            ImportColumn::make('assignees comma separated emails')
                ->array(',')
                ->rules(['nullable', 'array'])
                ->nestedRecursiveRules(['email']),

            ImportColumn::make('start date yyyy-mm-dd')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->castStateUsing(fn ($state) => Carbon::parse($state)),

            ImportColumn::make('due date yyyy-mm-dd')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->castStateUsing(fn ($state) => Carbon::parse($state)),
        ];
    }

    /**
     * Selalu create ticket baru
     */
    public function resolveRecord(): ?Ticket
    {
        return new Ticket();
    }

    /**
     * Mapping relasi sebelum save
     */
    public function beforeSave(): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->record;

        $project = $this->options['project'] ?? null;

        if (! $project) {
            throw new RowImportFailedException('Project context tidak ditemukan.');
        }

        // Status by name (project scoped)
        $status = TicketStatus::query()
            ->where('project_id', $project->id)
            ->where('name', $this->data['status'])
            ->first();

        if (! $status) {
            throw new RowImportFailedException("Status tidak valid: {$this->data['status']}");
        }

        // Priority by name
        $priority = TicketPriorities::where('name', $this->data['priority'])->first();

        if (! $priority) {
            throw new RowImportFailedException("Priority tidak valid: {$this->data['priority']}");
        }

        // Epic optional
        $epicId = null;
        if (! blank($this->data['epic'])) {
            $epic = Epic::query()
                ->where('project_id', $project->id)
                ->where('name', $this->data['epic'])
                ->first();

            if (! $epic) {
                throw new RowImportFailedException("Epic tidak valid: {$this->data['epic']}");
            }

            $epicId = $epic->id;
        }

        $ticket->project_id = $project->id;
        $ticket->ticket_status_id = $status->id;
        $ticket->priority_id = $priority->id;
        $ticket->epic_id = $epicId;
        $ticket->created_by = auth()->id();
    }

    /**
     * Sync assignees setelah save
     */
    public function afterSave(): void
    {
        if (empty($this->data['assignees comma separated emails'])) {
            return;
        }

        $emails = collect($this->data['assignees comma separated emails'])
            ->map(fn ($email) => trim($email));

        $userIds = User::whereIn('email', $emails)->pluck('id');

        $this->record->assignees()->sync($userIds);
    }
}