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
            ->subject('Seu código de verificação · '.config('app.name'))
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Recebemos uma tentativa de login na sua conta. Use o código abaixo para concluí-la:')
            ->line(new HtmlString($this->codeBox($this->code)))
            ->line('O código expira em **5 minutos**.')
            ->line('Se não foi você, ignore este e-mail — sua conta continua protegida.')
            ->salutation('Equipe '.config('app.name'));
    }

    private function codeBox(string $code): string
    {
        return '<div style="text-align:center;margin:28px 0;">'
            .'<span style="display:inline-block;padding:16px 32px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;'
            .'font-size:30px;font-weight:700;letter-spacing:8px;color:#0134ac;background:#eef2ff;'
            .'border:1px solid #c7d2fe;border-radius:12px;">'.e($code).'</span>'
            .'</div>';
    }
}
