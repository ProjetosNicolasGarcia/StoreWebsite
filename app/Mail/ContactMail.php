<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data; // Aqui guardamos os dados do formulário

    public function __construct($data)
    {
        $this->data = $data;
    }

   public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Contato do Site: ' . ($this->data['subject'] ?? 'Sem Assunto'),
        // ADICIONE ESTA LINHA ABAIXO:
        replyTo: [
            new Address($this->data['email'], $this->data['name'])
        ],
    );
}

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact', // Vamos criar essa view no próximo passo
        );
    }

    public function attachments(): array
    {
        return [];
    }
}