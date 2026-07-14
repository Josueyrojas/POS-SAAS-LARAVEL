<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStockAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $businessName,
        private readonly Collection $products,
        private readonly string $productsUrl,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Stock bajo en {$this->businessName}")
            ->line('Estos productos están en su punto mínimo de stock o por debajo:');

        foreach ($this->products as $product) {
            $message->line("• {$product->name}: {$this->formatQty($product->stock)} disponibles (mínimo {$this->formatQty($product->stock_minimo)})");
        }

        return $message
            ->action('Ver inventario', $this->productsUrl)
            ->line('Este aviso se manda como máximo una vez al día por negocio.');
    }

    private function formatQty(string $qty): string
    {
        return rtrim(rtrim(number_format((float) $qty, 3), '0'), '.');
    }
}
