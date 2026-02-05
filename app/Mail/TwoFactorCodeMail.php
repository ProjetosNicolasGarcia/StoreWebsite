<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable responsável pelo envio do código de Autenticação de Dois Fatores (2FA).
 * Garante a segurança do login enviando um código numérico temporário.
 */
class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * O código de verificação (geralmente 6 dígitos) que será exibido no e-mail.
     * @var int|string
     */
    public $code;

    /**
     * Cria uma nova instância da mensagem.
     *
     * @param int|string $code O código gerado no Controller.
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Define o envelope do e-mail (Assunto e Remetente).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu Código de Login - StoreWebsite',
        );
    }

    /**
     * Define o conteúdo visual do e-mail.
     * Aponta para a view Blade que renderiza o código.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.two_factor', // Certifique-se que este arquivo existe em resources/views/emails/
        );
    }

    /**
     * Define anexos do e-mail (nenhum neste caso).
     */
    public function attachments(): array
    {
        return [];
    }
}