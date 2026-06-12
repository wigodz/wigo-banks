<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WithdrawalCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirme sua solicitação de saque')
            ->line('Use o código abaixo para confirmar sua solicitação de saque.')
            ->line(new HtmlString("<strong>{$this->code}</strong>"))
            ->line('Este código expira em 5 minutos.')
            ->line('Se você não solicitou este saque, ignore este e-mail.');
    }
}
