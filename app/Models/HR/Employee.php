<?php
namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'employee_id');
    }
}
