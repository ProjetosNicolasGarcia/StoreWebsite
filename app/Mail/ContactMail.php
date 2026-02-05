<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

/**
 * Mailable responsável pelo envio de mensagens do formulário de "Fale Conosco".
 * Encaminha as dúvidas dos clientes para o e-mail administrativo da loja.
 */
class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Armazena os dados validados do formulário (nome, email, mensagem, etc).
     * @var array
     */
    public $data;

    /**
     * Cria uma nova instância da mensagem.
     *
     * @param array $data Dados recebidos do Controller.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Define o envelope do e-mail (Assunto, Remetente e Destinatários).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contato do Site: ' . ($this->data['subject'] ?? 'Sem Assunto'),
            
            // [UX/Usabilidade] Configura o "Responder Para" (Reply-To).
            // Permite que o admin clique em "Responder" no seu cliente de e-mail (Gmail/Outlook)
            // e a resposta vá diretamente para o cliente, em vez do e-mail do sistema.
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
            view: 'emails.contact', // Aponta para resources/views/emails/contact.blade.php
        );
    }

    /**
     * Define anexos do e-mail (vazio por padrão).
     */
    public function attachments(): array
    {
        return [];
    }
}