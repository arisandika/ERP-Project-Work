<?php

namespace App\Mail;

use App\Filament\Pages\ProjectBoard;
use App\Models\HR\Employee;
use App\Models\Project\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectAssignmentNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public Employee $assignedUser,
        public Employee $assignedBy
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You have been added to the project: {$this->project->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-assignment',
            with: [
                'project' => $this->project,
                'assignedUser' => $this->assignedUser,
                'assignedBy' => $this->assignedBy,
                // 'projectUrl' => ProjectBoard::getUrl(['project_id' => $this->project->id]),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
