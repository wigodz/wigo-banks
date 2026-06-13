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
            ->subject('Confirme seu saque · '.config('app.name'))
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Você solicitou um saque. Use o código abaixo para confirmá-lo:')
            ->line(new HtmlString($this->codeBox($this->code)))
            ->line('O código expira em **5 minutos**.')
            ->line('Se você não solicitou este saque, ignore este e-mail e nenhum valor será debitado.')
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
