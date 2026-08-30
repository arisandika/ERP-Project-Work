<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceEvaluation extends Model
{
    use SoftDeletes;

    protected $table = 'nx_performance_evaluations';

    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'period',
        'rating',
        'feedback',
        'evaluated_at',
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
        'rating' => 'integer',
    ];

    public const RATINGS = [
        1 => 'Very Poor',
        2 => 'Poor',
        3 => 'Average',
        4 => 'Good',
        5 => 'Outstanding',
    ];

    /**
     * Get the rating label for display.
     */
    public function getRatingLabelAttribute(): string
    {
        return self::RATINGS[$this->rating] ?? (string) $this->rating;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }
}
