<?php

namespace App\Models\Project;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ProjectNote extends Model
{
    use HasFactory;

    protected $table = 'nx_project_notes';

    protected $fillable = [
        'project_id',
        'created_by',
        'title',
        'content',
        'note_date',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function getFormattedNoteDateAttribute(): string
    {
        return Carbon::parse($this->note_date)->format('M d, Y');
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}