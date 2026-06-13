<?php

namespace App\Listeners\UserCreated;

use App\Notifications\WelcomeNotification;

class SendWelcomeNotification
{
    public function handle($event): void
    {
        $event->user->notify(new WelcomeNotification);
    }
}
