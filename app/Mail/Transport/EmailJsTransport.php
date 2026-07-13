<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

/**
 * EmailJS envía por su API HTTPS (nunca bloqueada) usando la cuenta de Gmail
 * conectada en su dashboard — a diferencia del sandbox de Mailgun, entrega a
 * cualquier destinatario sin necesitar un dominio propio verificado. Es una
 * solución temporal mientras no haya dominio (ver plantilla en EmailJS: debe
 * tener las variables to_email, subject y message_html).
 */
class EmailJsTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $serviceId,
        private readonly string $templateId,
        private readonly string $publicKey,
        private readonly string $privateKey,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $to = $email->getTo()[0]->getAddress();

        // La plantilla de EmailJS escapa el contenido de las variables sin
        // importar cuántas llaves se usen (no soporta HTML crudo vía
        // variables) — se manda el texto plano y la propia plantilla
        // preserva los saltos de línea con "white-space: pre-line".
        $textBody = trim((string) ($email->getTextBody() ?? strip_tags((string) $email->getHtmlBody())));

        $response = Http::asJson()->post('https://api.emailjs.com/api/v1.0/email/send', [
            'service_id' => $this->serviceId,
            'template_id' => $this->templateId,
            'user_id' => $this->publicKey,
            'accessToken' => $this->privateKey,
            'template_params' => [
                'to_email' => $to,
                'subject' => $email->getSubject(),
                'message_html' => $textBody,
            ],
        ]);

        if ($response->failed()) {
            throw new TransportException('EmailJS rechazó el envío: '.$response->body());
        }
    }

    public function __toString(): string
    {
        return 'emailjs';
    }
}
