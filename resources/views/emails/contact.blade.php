<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .header { background: #000; color: #fff; padding: 15px; text-align: center; text-transform: uppercase; letter-spacing: 2px; }
        .content { padding: 20px 0; }
        .info-box { background: #f9f9f9; padding: 15px; border-left: 4px solid #000; margin-bottom: 20px; }
        .info-box p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Nova Solicitação de Suporte
        </div>
        
        <div class="content">
            <div class="info-box">
                <p><strong>Nome:</strong> {{ $mailData['name'] }}</p>
                <p><strong>E-mail:</strong> {{ $mailData['email'] }}</p>
                <p><strong>Assunto:</strong> {{ $mailData['subject'] }}</p>
                @if(!empty($mailData['order_id']))
                    <p><strong>Ref. ao Pedido:</strong> #{{ str_pad($mailData['order_id'], 6, '0', STR_PAD_LEFT) }}</p>
                @endif
            </div>

            <h3>Mensagem:</h3>
            <p>{!! nl2br(e($mailData['message'])) !!}</p>
        </div>
    </div>
</body>
</html>