<?php

namespace App\Listeners;

use App\Events\TwoFactorCodeRequested;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTwoFactorCodeNotification implements ShouldQueue
{
    /**
     * The name of the queue the listener should be dispatched to.
     *
     * Login is processed on a dedicated queue with a single worker so that
     * only one two-factor code is sent at a time.
     */
    public string $queue = 'login';

    public function handle(TwoFactorCodeRequested $event): void
    {
        $event->user->notify(new TwoFactorCodeNotification($event->code));
    }
}
