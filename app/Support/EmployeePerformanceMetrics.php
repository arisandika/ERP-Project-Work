<?php

namespace App\Support;

use App\Models\HR\Employee;
use App\Models\HR\PerformanceEvaluation;
use App\Models\Project\Ticket;
use App\Models\Project\TicketHistory;
use App\Models\Project\TicketStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes performance metrics for an employee from existing
 * Project Management + HR data. No HR dashboard is created; this is
 * the single source of truth used by the Employee Performance list,
 * detail view, and blade page.
 */
class EmployeePerformanceMetrics
{
    /**
     * Aggregates all performance metrics for a single employee.
     *
     * Tasks are scoped to tickets the employee is assigned to OR created,
     * matching the existing ProjectsRelationManager::computeMetric scope.
     */
    public static function forEmployee(Employee $employee, ?Carbon $from = null, ?Carbon $to = null): object
    {
        $employeeId = $employee->id;

        $totalProjects = $employee->projects()->count();

        // Completed ticket status ids.
        $completedStatusIds = TicketStatus::where('is_completed', true)->pluck('id')->toArray();

        // "Completed project" = no open (non-completed) tickets remain.
        $openTicketProjectIds = Ticket::whereNotIn('ticket_status_id', $completedStatusIds)
            ->when($from, fn($q) => $q->where(function ($sq) use ($from) {
                $sq->where('due_date', '>=', $from->toDateString())
                    ->orWhere('created_at', '>=', $from);
            }))
            ->when($to, fn($q) => $q->where(function ($sq) use ($to) {
                $sq->where('due_date', '<=', $to->toDateString())
                    ->orWhere('created_at', '<=', $to);
            }))
            ->whereNotNull('project_id')
            ->distinct()
            ->pluck('project_id')
            ->toArray();

        $completedProjects = $totalProjects > 0
            ? $employee->projects()
                ->whereNotIn('nx_projects.id', $openTicketProjectIds ?: [0])
                ->count()
            : 0;

        $tickets = Ticket::query()
            ->where(function ($q) use ($employeeId) {
                $q->whereHas('assignees', fn($s) => $s->where('employee_id', $employeeId))
                    ->orWhere('created_by', $employeeId);
            })
            ->when($from, fn($q) => $q->where(function ($sq) use ($from) {
                $sq->where('due_date', '>=', $from->toDateString())
                    ->orWhere('created_at', '>=', $from);
            }))
            ->when($to, fn($q) => $q->where(function ($sq) use ($to) {
                $sq->where('due_date', '<=', $to->toDateString())
                    ->orWhere('created_at', '<=', $to);
            }))
            ->get();

        $totalTasks = $tickets->count();
        $completedTasks = $tickets->whereIn('ticket_status_id', $completedStatusIds)->count();
        $overdueTasks = $tickets->filter(function ($t) use ($completedStatusIds) {
            if (in_array($t->ticket_status_id, $completedStatusIds)) {
                return false;
            }

            return $t->due_date && Carbon::today()->gt($t->due_date);
        })->count();

        $completedOnTime = $tickets
            ->filter(fn($t) => in_array($t->ticket_status_id, $completedStatusIds))
            ->filter(fn($t) => self::wasCompletedOnTime($t, $completedStatusIds))
            ->count();

        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 1)
            : 0.0;

        // Supervisor evaluation average (within period if given).
        $evalQuery = PerformanceEvaluation::where('employee_id', $employeeId)
            ->when($from, fn($q) => $q->where('evaluated_at', '>=', $from))
            ->when($to, fn($q) => $q->where('evaluated_at', '<=', $to));

        $evalAvg = $evalQuery->get()->avg('criterion_average');

        $score = self::computeScore(
            $completionRate,
            $totalTasks,
            $overdueTasks,
            $evalAvg !== null ? (float) $evalAvg : null,
        );

        return (object) [
            'totalProjects'      => $totalProjects,
            'completedProjects'  => $completedProjects,
            'totalTasks'         => $totalTasks,
            'completedTasks'     => $completedTasks,
            'overdueTasks'       => $overdueTasks,
            'completedOnTime'    => $completedOnTime,
            'completionRate'     => $completionRate,
            'evaluationAverage'  => $evalAvg !== null ? round((float) $evalAvg, 2) : null,
            'performanceScore'   => $score,
            'status'             => self::statusFromScore($score),
        ];
    }

    /**
     * A completed ticket is "on time" if the last transition into a
     * completed status (per TicketHistory) occurred on/before its due_date.
     * Without history, fall back to on-time (no deadline = on time).
     */
    protected static function wasCompletedOnTime(Ticket $ticket, array $completedStatusIds): bool
    {
        if (! $ticket->due_date) {
            return true;
        }

        $history = TicketHistory::where('ticket_id', $ticket->id)
            ->whereIn('ticket_status_id', $completedStatusIds)
            ->latest('created_at')
            ->first();

        if (! $history) {
            return true;
        }

        return Carbon::parse($history->created_at)->lte(Carbon::parse($ticket->due_date));
    }

    /**
     * Score (0–100) = completionRate·50% + (100 − overdueRate)·30% + evaluation·20%.
     * If no tasks/projects exist, score falls back to evaluation-only (or 0).
     */
    protected static function computeScore(float $completionRate, int $totalTasks, int $overdue, ?float $evalAvg): float
    {
        if ($totalTasks === 0 && $evalAvg === null) {
            return 0.0;
        }

        if ($totalTasks === 0) {
            return round(($evalAvg / 5) * 100, 1);
        }

        $overdueRate = ($overdue / $totalTasks) * 100;
        $activity = ($completionRate * 0.5) + ((100 - $overdueRate) * 0.3);

        $score = $evalAvg !== null
            ? ($activity + (($evalAvg / 5) * 100) * 0.2)
            : $activity;

        return round($score, 1);
    }

    public static function statusFromScore(float $score): string
    {
        if ($score >= 80) {
            return 'Excellent';
        }
        if ($score >= 60) {
            return 'Good';
        }

        return 'Needs Improvement';
    }

    /**
     * Historical trend of an evaluation metric grouped by period.
     */
    public static function trend(Employee $employee, ?array $range = null): Collection
    {
        return PerformanceEvaluation::where('employee_id', $employee->id)
            ->when($range, fn($q) => $q->whereBetween('evaluated_at', $range))
            ->select(['period', 'rating'])
            ->orderBy('period')
            ->get();
    }
}
