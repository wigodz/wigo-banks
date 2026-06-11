<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

class LogUserCreated
{
    public function handle($event): void
    {
        Log::info('Usuário criado', [
            'hash' => $event->user->hash,
            'email' => $event->user->email,
        ]);
    }
}
