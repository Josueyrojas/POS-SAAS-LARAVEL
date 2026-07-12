<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $resetUrl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recupera tu contraseña')
            ->line('Recibimos una solicitud para restablecer tu contraseña.')
            ->action('Restablecer contraseña', $this->resetUrl)
            ->line('Si no pediste esto, puedes ignorar este correo. El enlace expira en 60 minutos.');
    }
}
