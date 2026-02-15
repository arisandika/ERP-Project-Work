<?php

namespace App\Models\Project;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;
    
    protected $table = 'nx_tickets';

    protected $fillable = [
        'project_id',
        'ticket_status_id',
        'priority_id',
        'name',
        'description',
        'start_date',
        'due_date',
        'uuid',
        'epic_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($ticket) {
            if (empty($ticket->uuid)) {
                $project = Project::find($ticket->project_id);
                $prefix = $project ? $project->ticket_prefix : 'TKT';
                $randomString = Str::upper(Str::random(6));

                $ticket->uuid = "{$prefix}-{$randomString}";
            }

            // Set created_by jika belum di-set dan ada user yang login
            if (empty($ticket->created_by) && auth()->user()->employee->id) {
                $ticket->created_by = auth()->user()->employee->id;
            }
        });

        static::updating(function ($ticket) {
            if ($ticket->isDirty('ticket_status_id')) {
                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'employee_id' => auth()->user()->employee->id,
                    'ticket_status_id' => $ticket->ticket_status_id,
                ]);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'nx_ticket_users');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class)->orderBy('created_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'asc');
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    public function assignUser(Employee $employee): void
    {
        $this->assignees()->syncWithoutDetaching($employee->id);
    }

    public function unassignUser(Employee $employee): void
    {
        $this->assignees()->detach($employee->id);
    }

    public function assignUsers(array $employeeIds): void
    {
        $this->assignees()->sync($employeeIds);
    }

    public function isAssignedTo(Employee $employee): bool
    {
        return $this->assignees()->where('employee_id', $employee->id)->exists();
    }
}
