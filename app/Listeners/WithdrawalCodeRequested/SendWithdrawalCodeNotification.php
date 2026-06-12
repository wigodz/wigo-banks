<?php

namespace App\Listeners\WithdrawalCodeRequested;

use App\Notifications\WithdrawalCodeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWithdrawalCodeNotification implements ShouldQueue
{
    public function handle($event): void
    {
        $event->user->notify(new WithdrawalCodeNotification($event->code));
    }
}
