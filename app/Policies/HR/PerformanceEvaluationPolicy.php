<?php

namespace App\Policies\HR;

use App\Models\User;
use App\Models\HR\Employee;
use App\Models\HR\PerformanceEvaluation;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerformanceEvaluationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_hr::r::performance_evaluation');
    }

    /**
     * Determine whether the user can view the model.
     * Supervisor or evaluator can view evaluations for their subordinates.
     */
    public function view(User $user, PerformanceEvaluation $evaluation): bool
    {
        $employee = $user->employee;

        return $employee
            && ($employee->id === $evaluation->evaluator_id
                || $employee->subordinates()->where('id', $evaluation->employee_id)->exists()
                || $employee->id === $evaluation->employee->supervisor_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_hr::r::performance_evaluation');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PerformanceEvaluation $evaluation): bool
    {
        $employee = $user->employee;

        if ($employee && $employee->id === $evaluation->evaluator_id) {
            return true;
        }

        return $user->can('update_hr::r::performance_evaluation');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PerformanceEvaluation $evaluation): bool
    {
        return $user->can('delete_hr::r::performance_evaluation');
    }

    /**
     * Determine whether the user can view the model's metrics.
     */
    public function viewMetrics(User $user, Employee $employee): bool
    {
        $viewer = $user->employee;

        if (!$viewer) {
            return false;
        }

        // Supervisor sees subordinates; self can always view own metrics
        return $viewer->id === $employee->supervisor_id
            || $viewer->subordinates()->where('id', $employee->id)->exists();
    }
}
