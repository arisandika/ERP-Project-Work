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
        'quality',
        'teamwork',
        'communication',
        'problem_solving',
        'feedback',
        'evaluated_at',
    ];

    public const RATINGS = [
        1 => 'Very Poor',
        2 => 'Poor',
        3 => 'Average',
        4 => 'Good',
        5 => 'Outstanding',
    ];

    /**
     * Per-criterion rating options (reuses the same 1-5 scale/labels).
     * 'rating' itself acts as the overall rating.
     */
    public const CRITERIA = [
        'rating'        => 'Overall Rating',
        'quality'       => 'Quality of Work',
        'teamwork'      => 'Teamwork',
        'communication' => 'Communication',
        'problem_solving' => 'Problem Solving',
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
        'rating' => 'integer',
        'quality' => 'integer',
        'teamwork' => 'integer',
        'communication' => 'integer',
        'problem_solving' => 'integer',
    ];

    /**
     * Get the rating label for display.
     */
    public function getRatingLabelAttribute(): string
    {
        return self::RATINGS[$this->rating] ?? (string) $this->rating;
    }

    /**
     * Average of all populated criteria (overall + per-criterion), or null.
     */
    public function getCriterionAverageAttribute(): ?float
    {
        $values = [];
        foreach (self::CRITERIA as $col => $_) {
            if (! is_null($this->{$col})) {
                $values[] = (int) $this->{$col};
            }
        }

        return $values ? round(array_sum($values) / count($values), 2) : null;
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
