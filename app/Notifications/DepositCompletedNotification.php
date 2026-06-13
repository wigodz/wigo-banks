<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $amount,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = 'R$ '.number_format($this->amount / 100, 2, ',', '.');

        return (new MailMessage)
            ->subject('Depósito confirmado · '.config('app.name'))
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line("Seu depósito de **{$amount}** foi concluído com sucesso e já está disponível no seu saldo.")
            ->salutation('Equipe '.config('app.name'));
    }
}
