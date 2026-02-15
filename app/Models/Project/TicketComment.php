<?php

namespace App\Models\Project;

use App\Models\HR\Employee;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketComment extends Model
{
    protected $table = 'nx_ticket_comments';

    protected $fillable = [
        'ticket_id',
        'employee_id',
        'comment',
    ];

    protected static function booted()
    {
        static::created(function ($comment) {
            app(NotificationService::class)->notifyCommentAdded($comment);
        });

        static::updated(function ($comment) {
            if ($comment->wasChanged('comment')) {
                app(NotificationService::class)->notifyCommentUpdated($comment);
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
