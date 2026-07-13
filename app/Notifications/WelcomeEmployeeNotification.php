<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeEmployeeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $businessName,
        private readonly string $setPasswordUrl,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Te damos la bienvenida a {$this->businessName}")
            ->line("Se creó tu cuenta de empleado en {$this->businessName}.")
            ->action('Definir mi contraseña', $this->setPasswordUrl)
            ->line('El enlace expira en 60 minutos. Si no reconoces esta invitación, puedes ignorar este correo.');
    }
}
