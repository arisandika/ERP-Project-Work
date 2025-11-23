<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HR\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestAdminNotification;
use App\Notifications\LeaveRequestEmployeeNotification;

class TestLeaveRequestNotification extends Command
{
    protected $signature = 'test:leave-request {id}';
    protected $description = 'Test notification leave request';

    public function handle()
    {
        $leaveRequest = LeaveRequest::find($this->argument('id'));

        if (! $leaveRequest) {
            $this->error('Leave request not found.');
            return 1;
        }

        $user = User::first();

        $this->info("Sending admin & employee notifications...");

        $user->notify(new LeaveRequestAdminNotification($leaveRequest));
        $user->notify(new LeaveRequestEmployeeNotification($leaveRequest));

        $this->info("Done.");

        return 0;
    }
}
