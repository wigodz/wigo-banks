<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bem-vindo(a) ao '.config('app.name').'!')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Sua conta foi criada com sucesso. 🎉')
            ->line('Agora você já pode depositar, transferir valores e acompanhar todo o seu histórico de movimentações.')
            ->action('Acessar minha conta', url('/dashboard'))
            ->line('Estamos felizes em ter você com a gente!')
            ->salutation('Equipe '.config('app.name'));
    }
}
