<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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
        'full_name',
        'email',
        'phone_number',
        'photo',
        'address',
        'position',
        'contract_type',
        'status',
        'face_embeddings',
        'face_embedding_path',
        'face_landmarks',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'employee_id');
    }
}
