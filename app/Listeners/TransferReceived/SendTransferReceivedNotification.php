<?php

namespace App\Listeners\TransferReceived;

use App\Notifications\TransferReceivedNotification;

class SendTransferReceivedNotification
{
    public function handle($event): void
    {
        $event->recipient->notify(
            new TransferReceivedNotification($event->sender->name, $event->amount),
        );
    }
}
