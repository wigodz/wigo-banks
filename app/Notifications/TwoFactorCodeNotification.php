<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TwoFactorCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $code,
    ) {
        // Login is processed on a dedicated queue with a single worker so
        // that only one two-factor code is sent at a time.
        $this->onQueue('login');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Seu código de verificação')
            ->line('Use o código abaixo para confirmar seu login.')
            ->line(new HtmlString("<strong>{$this->code}</strong>"))
            ->line('Este código expira em 5 minutos.')
            ->line('Se você não tentou fazer login, ignore este e-mail.');
    }
}
