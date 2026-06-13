<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly User $sender,
        public readonly int $amount,
    ) {}
}
