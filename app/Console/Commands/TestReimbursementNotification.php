<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Finance\ReimbursementRequest;
use App\Models\User;
use App\Notifications\ReimbursementAdminNotification;
use App\Notifications\ReimbursementEmployeeNotification;

class TestReimbursementNotification extends Command
{
    protected $signature = 'test:reimburse {id}';
    protected $description = 'Test notification reimbursement request';

    public function handle()
    {
        $reimbursement = ReimbursementRequest::find($this->argument('id'));

        if (! $reimbursement) {
            $this->error('Reimbursement not found.');
            return 1;
        }

        $user = User::first();

        $this->info("Sending admin & employee notifications...");

        $user->notify(new ReimbursementAdminNotification($reimbursement));
        $user->notify(new ReimbursementEmployeeNotification($reimbursement));

        $this->info("Done.");

        return 0;
    }
}