<?php

namespace App\Services;

use App\Models\HR\Employee;
use App\Models\Project\Notification;
use App\Models\Project\Project;
use App\Models\Project\Ticket;
use App\Models\Project\TicketComment;
use Exception;
use App\Mail\ProjectAssignmentNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function notifyCommentAdded(TicketComment $comment): void
    {
        $ticket = $comment->ticket;
        $commenter = $comment->employee;

        $usersToNotify = $this->getUsersToNotifyForComment($ticket, $commenter);

        foreach ($usersToNotify as $employee) {
            Notification::create([
                'employee_id' => $employee->id,
                'type' => 'comment_added',
                'title' => 'New Comment on Ticket',
                'message' => "{$commenter->name} added a comment on ticket {$ticket->title}",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'comment_id' => $comment->id,
                    'commenter_id' => $commenter->id,
                    'commenter_name' => $commenter->name,
                ],
            ]);
        }
    }

    public function notifyCommentUpdated(TicketComment $comment): void
    {
        $ticket = $comment->ticket;
        $commenter = $comment->employee;

        $usersToNotify = $this->getUsersToNotifyForComment($ticket, $commenter);

        foreach ($usersToNotify as $employee) {
            Notification::create([
                'employee_id' => $employee->id,
                'type' => 'comment_updated',
                'title' => 'Comment Updated',
                'message' => "{$commenter->name} updated a comment on ticket {$ticket->title}",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'comment_id' => $comment->id,
                    'commenter_id' => $commenter->id,
                    'commenter_name' => $commenter->name,
                ],
            ]);
        }
    }

    private function getUsersToNotifyForComment(Ticket $ticket, Employee $commenter): Collection
    {
        $usersToNotify = collect();

        if ($ticket->creator && $ticket->creator->id !== $commenter->id) {
            $usersToNotify->push($ticket->creator);
        }

        $assignedUsers = $ticket->assignees()->where('nx_employees.id', '!=', $commenter->id)->get();
        $usersToNotify = $usersToNotify->merge($assignedUsers);

        $commenters = $ticket->comments()
            ->with('employee')
            ->where('employee_id', '!=', $commenter->id)
            ->get()
            ->pluck('employee')
            ->unique('id');
        $usersToNotify = $usersToNotify->merge($commenters);

        return $usersToNotify->unique('id');
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('employee_id', $userId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    public function markAllAsRead(int $userId): void
    {
        Notification::where('employee_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function notifyProjectAssignment(Project $project, Employee $assignedUser, Employee $assignedBy): void
    {
        try {
            Notification::create([
                'employee_id' => $assignedUser->id,
                'type' => 'project_assigned',
                'title' => 'Added to Project',
                'message' => "You have been added to project '{$project->name}' by {$assignedBy->name}",
                'data' => [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'assigned_by_id' => $assignedBy->id,
                    'assigned_by_name' => $assignedBy->name,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create in-app notification: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'employee_id' => $assignedUser->id,
            ]);
        }

        try {
            $mail = new ProjectAssignmentNotification($project, $assignedUser, $assignedBy);
            Mail::to($assignedUser->email)->send($mail);
        } catch (Exception $e) {
            // Log error but don't fail the assignment
            Log::error('Failed to send project assignment email: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'employee_id' => $assignedUser->id,
                'assigned_by_id' => $assignedBy->id,
                'to_email' => $assignedUser->email,
            ]);
        }
    }

    public function notifyProjectRemoval(Project $project, Employee $removedUser, Employee $removedBy): void
    {
        Notification::create([
            'employee_id' => $removedUser->id,
            'type' => 'project_removed',
            'title' => 'Removed from Project',
            'message' => "You have been removed from project '{$project->name}' by {$removedBy->name}",
            'data' => [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'removed_by_id' => $removedBy->id,
                'removed_by_name' => $removedBy->name,
            ],
        ]);
    }
}
