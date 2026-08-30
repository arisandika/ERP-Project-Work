<?php
namespace App\Models\Project;

use App\Models\HR\Employee;
use App\Models\Sales\SalesOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Project extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'nx_projects';

    protected $fillable = [
        'name',
        'description',
        'ticket_prefix',
        'color',
        'start_date',
        'end_date',
        'pinned_date',

        // billing
        'nx_sales_order_id',
        'estimated_cost',
        'actual_cost',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'pinned_date' => 'datetime',
    ];

    // Get if the project is pinned
    public function getIsPinnedAttribute(): bool
    {
        return ! is_null($this->pinned_date);
    }

    // Pin the project
    public function pin(): void
    {
        $this->update(['pinned_date' => now()]);
    }

    // Unpin the project
    public function unpin(): void
    {
        $this->update(['pinned_date' => null]);
    }

    // Ticket statuses associated with the project
    public function ticketStatuses(): HasMany
    {
        return $this->hasMany(TicketStatus::class);
    }

    // Tickets associated with the project
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    // Members (employees) of the project
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'nx_project_members')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    // Employees associated with the project
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'nx_project_members')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function getRemainingDaysAttribute()
    {
        if (! $this->end_date) {
            return null;
        }

        $today   = Carbon::today();
        $endDate = Carbon::parse($this->end_date);

        if ($today->gt($endDate)) {
            return 0;
        }

        return $today->diffInDays($endDate);
    }

    public function getProgressPercentageAttribute(): float
    {
        $totalTickets = $this->tickets()->count();

        if ($totalTickets === 0) {
            return 0.0;
        }

        $completedTickets = $this->tickets()
            ->whereHas('status', function ($query) {
                $query->where('is_completed', true);
            })
            ->count();

        return round(($completedTickets / $totalTickets) * 100, 1);
    }

    public function externalAccess(): HasOne
    {
        return $this->hasOne(ExternalAccess::class);
    }

    public function generateExternalAccess()
    {
        $this->externalAccess()?->delete();

        return ExternalAccess::generateForProject($this->id);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'nx_sales_order_id');
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class, 'nx_project_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }
}
