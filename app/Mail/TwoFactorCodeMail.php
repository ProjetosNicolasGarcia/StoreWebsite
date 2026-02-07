<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // [IMPORTANTE] Interface para filas
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Headers;

/**
 * Mailable responsável pelo envio do código de Autenticação de Dois Fatores (2FA).
 * [OTIMIZAÇÃO] Implementa ShouldQueue para envio assíncrono de alta prioridade.
 */
class TwoFactorCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * O código de verificação.
     * @var int|string
     */
    public $code;

    /**
     * [ESCALABILIDADE] Configurações da Fila para 2FA.
     * Diferente de emails comuns, 2FA não deve ficar tentando por horas.
     * Se falhar, falha rápido para o usuário pedir outro.
     */
    public $tries = 3;       // Tenta no máximo 3 vezes
    public $backoff = 10;    // Espera apenas 10 segundos entre tentativas (urgência)
    public $timeout = 30;    // Tempo máximo de execução do worker

    /**
     * Cria uma nova instância da mensagem.
     * @param int|string $code
     */
    public function __construct($code)
    {
        $this->code = $code;
        
        // [DICA] Se você tiver filas separadas no Laravel (config/queue.php),
        // é recomendável forçar este e-mail para a fila de alta prioridade:
        // $this->onQueue('high'); 
    }

    /**
     * Define o envelope do e-mail.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu Código de Login - StoreWebsite',
            // Adiciona tags para serviços como Mailgun/SendGrid priorizarem a entrega
            tags: ['authentication', '2fa'],
            metadata: [
                'type' => '2fa_code',
            ],
        );
    }

    /**
     * Define o conteúdo visual do e-mail.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.two_factor',
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