<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // [IMPORTANTE] Interface para filas
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

/**
 * Mailable responsável pelo envio de mensagens do formulário de "Fale Conosco".
 * [OTIMIZAÇÃO] Implementa ShouldQueue para garantir que o envio seja assíncrono,
 * liberando o usuário imediatamente sem esperar a resposta do servidor SMTP.
 */
class ContactMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Armazena os dados validados do formulário.
     * @var array
     */
    public $data;

    /**
     * Cria uma nova instância da mensagem.
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Define o envelope do e-mail.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contato do Site: ' . ($this->data['subject'] ?? 'Sem Assunto'),
            replyTo: [
                new Address($this->data['email'], $this->data['name'])
            ],
        );
    }

    /**
     * Define o conteúdo visual do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
        );
    }

    /**
     * Define anexos do e-mail.
     */
    public function attachments(): array
    {
        return [];
    }
}