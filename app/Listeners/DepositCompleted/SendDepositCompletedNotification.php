<?php

namespace App\Listeners\DepositCompleted;

use App\Notifications\DepositCompletedNotification;

class SendDepositCompletedNotification
{
    public function handle($event): void
    {
        $event->user->notify(new DepositCompletedNotification($event->amount));
    }
}
