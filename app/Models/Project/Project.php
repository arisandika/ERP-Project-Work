<?php

namespace App\Models\Project;

use App\Models\HR\Employee;
use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Project extends Model
{
    use HasFactory;

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
        'nx_invoice_id',
        'sales_invoice_number',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pinned_date' => 'datetime',
    ];

    // Get if the project is pinned
    public function getIsPinnedAttribute(): bool
    {
        return !is_null($this->pinned_date);
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
            ->withTimestamps();
    }

    // Employees associated with the project
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'nx_project_members')
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
        if (!$this->end_date) {
            return null;
        }

        $today = Carbon::today();
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'nx_invoice_id');
    }

    public function salesPic()
    {
        return $this->belongsTo(Employee::class, 'sales_pic_id');
    }
}
