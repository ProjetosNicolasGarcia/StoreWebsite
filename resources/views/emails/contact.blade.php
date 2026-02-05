<!DOCTYPE html>
<html>
<head>
    <title>Novo Contato do Site</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    
    {{-- Título Principal do E-mail --}}
    <h2 style="color: #000;">Novo contato recebido pelo site</h2>
    
    {{-- Informações do Remetente e Assunto --}}
    <p><strong>Nome:</strong> {{ $data['name'] }}</p>
    <p><strong>E-mail:</strong> {{ $data['email'] }}</p>
    <p><strong>Motivo:</strong> {{ $data['subject'] }}</p>
    
    {{-- Exibição Condicional: Número do Pedido (Apenas se preenchido) --}}
    @if(!empty($data['order_number']))
        <p><strong>Número do Pedido:</strong> #{{ $data['order_number'] }}</p>
    @endif

    <hr>
    
    {{-- Corpo da Mensagem --}}
    <h3>Mensagem:</h3>
    <p style="background-color: #f4f4f4; padding: 15px; border-radius: 5px;">
        {{-- e(): Escapa HTML para segurança | nl2br(): Converte quebras de linha em <br> --}}
        {!! nl2br(e($data['message'])) !!}
    </p>

</body>
</html>