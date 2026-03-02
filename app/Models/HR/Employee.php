<?php
namespace App\Models\HR;

use App\Models\Finance\FinancialRecord;
use App\Models\Project\Notification;
use App\Models\Project\Project;
use App\Models\Project\Ticket;
use App\Models\User;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Employee extends Model
{
    use SoftDeletes, HasRoles;

    protected $guard_name = 'web';

    protected $table = 'nx_employees';

    protected $fillable = [
        'user_id',
        'department_id',
        'office_id',
        'national_id',     // NIK
        'identity_number', // No. KTP
        'full_name',
        'birth_place',
        'birth_date',
        'gender',
        'marital_status',
        'education_level',
        'join_date',
        'email',
        'phone_number',
        'address',
        'photo',
        'position',
        'contract_type',
        'status',
        'can_wfa',
        'can_unlock_shift',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'employee_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id', 'id');
    }

    // ================================ //
    // Project Management Relationships //
    // ================================ //

    // Projects the employee is a member of
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'nx_project_members')
            ->withTimestamps();
    }

    // Tickets created by or assigned to the employee
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    // Tickets assigned to the employee
    public function assignedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'nx_ticket_users');
    }

    // Tickets created by the employee
    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    // Methods
    public function isAssignedToTicket(Ticket $ticket): bool
    {
        return $this->assignedTickets()->where('ticket_id', $ticket->id)->exists();
    }

    // Assign the employee to a ticket
    public function assignToTicket(Ticket $ticket): void
    {
        $this->assignedTickets()->syncWithoutDetaching($ticket->id);
    }

    // Notifications
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    // Unread notifications
    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->unread()->orderBy('created_at', 'desc');
    }

    // Get count of unread notifications
    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->unreadNotifications()->count();
    }

    // Filament Panel Access
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class, 'created_by', 'id');
    }
}
