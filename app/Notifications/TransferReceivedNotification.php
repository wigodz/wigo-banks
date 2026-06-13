<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $senderName,
        public readonly int $amount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = 'R$ '.number_format($this->amount / 100, 2, ',', '.');

        return (new MailMessage)
            ->subject('Você recebeu uma transferência · '.config('app.name'))
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line("**{$this->senderName}** enviou **{$amount}** para a sua conta.")
            ->line('O valor já está disponível no seu saldo.')
            ->salutation('Equipe '.config('app.name'));
    }
}
