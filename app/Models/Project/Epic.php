<?php

namespace App\Models\Project;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Epic extends Model
{
    use HasFactory;
    
    protected $table = 'nx_epics';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
