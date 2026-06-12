<?php

namespace App\Listeners\TwoFactorCodeRequested;

use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTwoFactorCodeNotification implements ShouldQueue
{
    public string $queue = 'login';

    public function handle($event): void
    {
        $event->user->notify(new TwoFactorCodeNotification($event->code));
    }
}
