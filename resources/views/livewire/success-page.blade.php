<div class="bg-white min-h-screen pt-32 pb-10">
    <div class="container mx-auto px-4 max-w-7xl">
        
      {{-- Título Alinhado à Esquerda --}}
        <div class="flex items-center gap-4 mb-8">
            @if(in_array($order->payment_method, ['pix', 'boleto']))
                {{-- Ícone de Alerta/Atenção para pagamento pendente --}}
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 flex-shrink-0">
                    <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900">Falta pouco!</h1>
                    <p class="mt-1 text-sm text-gray-500">Seu pedido está quase lá. <strong class="text-gray-700">Realize o pagamento </strong> para prosseguirmos com o envio.</p>
                </div>
            @else
                {{-- Ícone de Sucesso para pagamento imediato (Cartão) --}}
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 flex-shrink-0">
                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900">Pedido Confirmado!</h1>
                    <p class="mt-1 text-sm text-gray-500">O pagamento foi aprovado! Enviamos um e-mail com todos os detalhes da sua compra.</p>
                </div>
            @endif
        </div>

        <div class="flex flex-col lg:flex-row gap-8 relative">
            
            {{-- COLUNA ESQUERDA: Detalhes do Pedido --}}
            <div class="w-full lg:w-2/3 space-y-6">
                
                {{-- Sessão 1: Código do Pedido --}}
                <section class="bg-white p-6 rounded-2xl border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span> 
                        Número do Pedido
                    </h2>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-500 uppercase tracking-widest font-bold">Identificador</p>
                            <p class="text-2xl font-black text-gray-900">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <div class="mt-4 sm:mt-0 text-left sm:text-right">
                            <p class="text-sm text-gray-500">Realizado em</p>
                            <p class="text-base font-bold text-gray-900">{{ $order->created_at->format('d/m/Y \à\s H:i') }}</p>
                        </div>
                    </div>
                </section>

                {{-- Sessão 2: Pagamento --}}
                <section class="bg-white p-6 rounded-2xl border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span> 
                        Pagamento
                    </h2>
                    
                    @if(in_array($order->payment_method, ['pix', 'boleto']))
                        @if($order->payment_method === 'pix')
                            <p class="text-sm text-gray-600 mb-6">Escaneie o QR Code abaixo pelo aplicativo do seu banco ou copie o código PIX.</p>
                            
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6" x-data="{ copyText: '{{ $order->pix_qr_code }}', copied: false }">
                                <div class="w-48 h-48 bg-gray-50 border border-gray-200 flex items-center justify-center rounded-xl overflow-hidden flex-shrink-0">
                                    @if($order->pix_qr_code_base64)
                                        <img src="data:image/jpeg;base64,{{ $order->pix_qr_code_base64 }}" alt="QR Code PIX" class="w-full h-full object-contain">
                                    @else
                                        <span class="text-xs text-gray-400">QR Code indisponível</span>
                                    @endif
                                </div>
                                
                                <div class="w-full">
                                    <label class="block text-sm font-medium text-gray-900 mb-2">Código PIX (Copia e Cola)</label>
                                    <div class="flex shadow-sm rounded-xl w-full flex-col sm:flex-row">
                                        <input type="text" readonly x-model="copyText" class="flex-1 block w-full rounded-t-xl sm:rounded-none sm:rounded-l-xl text-sm border border-gray-300 sm:border-r-0 bg-gray-50 text-gray-700 px-4 py-3 focus:ring-0 outline-none text-left">
                                        <button type="button" 
                                                @click="navigator.clipboard.writeText(copyText); copied = true; setTimeout(() => copied = false, 2500)" 
                                                class="relative w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border text-sm font-bold rounded-b-xl sm:rounded-none sm:rounded-r-xl transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                                :class="copied ? 'bg-white text-green-600 border-green-600 hover:bg-green-50' : 'bg-green-600 text-white border-green-600 hover:bg-white hover:text-green-600 focus:ring-green-600'">
                                            <span x-text="copied ? 'Copiado!' : 'Copiar'"></span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-3">O pagamento será compensado automaticamente em instantes.</p>
                                </div>
                            </div>
                        @elseif($order->payment_method === 'boleto')
                            <p class="text-sm text-gray-600 mb-6">Acesse o seu boleto abaixo para realizar o pagamento. O pedido será processado após a compensação.</p>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                                <div class="text-center sm:text-left">
                                    <p class="text-sm text-gray-500">Vencimento</p>
                                    <p class="text-lg font-bold text-gray-900">{{ now()->addDays(3)->format('d/m/Y') }}</p>
                                </div>
                               <a href="{{ $order->boleto_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex justify-center items-center px-8 py-3 border border-green-600 text-sm font-medium rounded-xl text-white bg-green-600 hover:bg-white hover:text-green-600 transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-600 w-full sm:w-auto">
                                    Imprimir / Baixar Boleto
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex items-center gap-4">
                            <div class="bg-white p-3 rounded-full border border-gray-200 flex-shrink-0">
                                <svg class="w-6 h-6 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">Cartão de Crédito</p>
                                <p class="text-sm text-gray-600">Aprovado e em processamento.</p>
                            </div>
                        </div>
                    @endif
                </section>

                {{-- Sessão 3: Informações do Cliente --}}
                <section class="bg-white p-6 rounded-2xl border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">3</span> 
                        Informações do Cliente
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-4">
                        <div>
                            <p class="text-sm font-bold text-gray-500">Nome</p>
                            <p class="text-base text-gray-900">{{ $order->user->name }} {{ $order->user->last_name ?? '' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-500">E-mail</p>
                            <p class="text-base text-gray-900">{{ $order->user->email }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-500">CPF</p>
                            <p class="text-base text-gray-900">{{ $order->user->cpf ?? 'Não informado' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-500">Telefone</p>
                            <p class="text-base text-gray-900">{{ $order->user->phone ?? 'Não informado' }}</p>
                        </div>
                    </div>
                </section>

                {{-- Sessão 4: Endereço de Entrega & Frete --}}
                <section class="bg-white p-6 rounded-2xl border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">4</span> 
                        Entrega e Frete
                    </h2>
                    
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col md:flex-row md:justify-between md:items-center gap-6">
                        @php
                            $addr = is_array($order->address_json) ? $order->address_json : json_decode($order->address_json, true);
                        @endphp
                        
                        <div class="flex-1">
                            @if($addr)
                                <p class="font-bold text-gray-900">{{ $addr['street'] ?? '' }}, {{ $addr['number'] ?? '' }} @if(!empty($addr['complement'])) - {{ $addr['complement'] }} @endif</p>
                                <p class="text-sm text-gray-600">{{ $addr['neighborhood'] ?? '' }} - {{ $addr['city'] ?? '' }}/{{ $addr['state'] ?? '' }}</p>
                                <p class="text-xs text-gray-500 mt-1">CEP: {{ $addr['zip_code'] ?? '' }}</p>
                            @else
                                <p class="text-sm text-gray-500">Endereço não disponível.</p>
                            @endif
                        </div>
                        
                        <div class="text-left md:text-right border-t md:border-t-0 md:border-l border-gray-200 pt-4 md:pt-0 md:pl-6 min-w-[160px] space-y-3">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Método de Envio</p>
                                <p class="font-medium text-sm text-gray-900 uppercase">
                                    {{ $order->shipping_method ?? 'Correios / Transportadora' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Custo do Frete</p>
                                <p class="font-black text-lg text-gray-900">
                                    {{ $order->shipping_cost > 0 ? 'R$ ' . number_format($order->shipping_cost, 2, ',', '.') : 'Grátis' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Botão de Ação Inferior (Desktop) - Oculto em ecrãs pequenos --}}
                <div class="pt-2 hidden lg:block">
                    <a href="{{ route('profile.orders') }}" class="w-full inline-flex justify-center items-center py-4 border border-black rounded-xl text-base font-bold uppercase tracking-widest text-white bg-black hover:bg-white hover:text-black transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                        Ver Todos os Pedidos
                    </a>
                </div>

            </div>

           {{-- COLUNA DIREITA: Resumo do Pedido --}}
            <div class="w-full lg:w-1/3">
                <div class="bg-white p-6 rounded-2xl border border-gray-200 sticky top-24 relative">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 uppercase tracking-tight">Resumo da Compra</h2>
                    
                    <div class="space-y-4 mb-6 pr-2">
                        @foreach($order->items as $item)
                            @php
                                $img = $item->product->image_url; 
                                if ($item->variant) {
                                    if ($item->variant->image) {
                                        $img = $item->variant->image;
                                    } elseif (!empty($item->variant->images) && isset($item->variant->images[0])) {
                                        $img = $item->variant->images[0];
                                    } 
                                    else {
                                        $colorOptions = ['Cor', 'Color', 'COR', 'cor', 'color'];
                                        $variantColor = null;
                                        if (is_array($item->variant->options)) {
                                            foreach ($colorOptions as $key) {
                                                if (isset($item->variant->options[$key])) {
                                                    $variantColor = $item->variant->options[$key];
                                                    break;
                                                }
                                            }
                                        }
                                        if ($variantColor && $item->product->variants) {
                                            foreach ($item->product->variants as $sibling) {
                                                if ($sibling->id === $item->variant->id) continue;
                                                $siblingColor = null;
                                                if ($sibling->options) {
                                                    foreach ($colorOptions as $key) {
                                                        if (isset($sibling->options[$key])) {
                                                            $siblingColor = $sibling->options[$key];
                                                            break;
                                                        }
                                                    }
                                                }
                                                if ($siblingColor === $variantColor) {
                                                    if ($sibling->image) {
                                                        $img = $sibling->image;
                                                        break;
                                                    } elseif (!empty($sibling->images) && isset($sibling->images[0])) {
                                                        $img = $sibling->images[0];
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            @endphp

                            <div class="flex gap-4">
                                <div class="w-16 h-20 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0 border border-gray-200 flex items-center justify-center">
                                    <img src="{{ Storage::url($img) }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 flex flex-col justify-between py-1">
                                    <div>
                                        <p class="font-bold text-sm text-gray-900 line-clamp-2">{{ $item->product->name ?? $item->product_name }}</p>
                                        
                                        @if($item->variant && is_array($item->variant->options) && count($item->variant->options) > 0)
                                            <div class="flex flex-wrap gap-x-3 text-xs text-gray-500 mt-1">
                                                @foreach($item->variant->options as $key => $value)
                                                    <span><strong class="font-semibold text-gray-700">{{ ucfirst($key) }}:</strong> {{ $value }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                        <p class="text-xs text-gray-500 mt-1">Qtd: {{ $item->quantity }}</p>
                                    </div>
                               
                                </div>
                                {{-- PREÇO ATUALIZADO: Exibe o valor total daquela linha (já com desconto vindo do banco) --}}
                                <div class="mt-2">
                                    <p class="font-bold text-sm text-gray-900">
                                        R$ {{ number_format($item->unit_price * $item->quantity, 2, ',', '.') }}
                                    </p>
                                    @if($item->quantity > 1)
                                        <p class="text-[10px] text-gray-400">
                                            (R$ {{ number_format($item->unit_price, 2, ',', '.') }} cada)
                                        </p>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>

    @php
                        // Cálculo dinâmico para separar Ofertas de Cupões na página de sucesso
                        $totalOriginal = 0;
                        foreach($order->items as $item) {
                            // Puxa o preço de tabela base, com proteção contra null. Usa unit_price como fallback de segurança.
                            $basePrice = (float) ($item->variant ? $item->variant->price : ($item->product->base_price ?? $item->unit_price));
                            $totalOriginal += ($basePrice * $item->quantity);
                        }
                        
                        // Economia de Ofertas = Preço Base Total - (Subtotal Pago antes do cupão)
                        // Usa round() para anular erros de float
                        $paidSubtotal = (float) $order->total_amount - (float) $order->shipping_cost + (float) $order->discount;
                        $savingsFromOffers = round($totalOriginal - $paidSubtotal, 2);
                    @endphp

                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        {{-- 1. Valor Original --}}
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Preço</span>
                            <span class="font-medium text-gray-900">R$ {{ number_format($totalOriginal, 2, ',', '.') }}</span>
                        </div>

                        {{-- 2. Economia em Ofertas: CORREÇÃO PARA > 0.01 --}}
                        @if($savingsFromOffers > 0.01)
                            <div class="flex justify-between text-sm text-emerald-600 font-bold">
                                <span>Desconto de oferta</span>
                                <span>- R$ {{ number_format($savingsFromOffers, 2, ',', '.') }}</span>
                            </div>
                        @endif

                        {{-- 3. Desconto de Cupão: CORREÇÃO PARA > 0.01 --}}
                        @if($order->discount > 0.01)
                            <div class="flex justify-between text-sm text-emerald-600 font-bold">
                                <span>Desconto de Cupom</span>
                                <span>- R$ {{ number_format($order->discount, 2, ',', '.') }}</span>
                            </div>
                        @endif

                        {{-- 4. Frete --}}
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Frete</span>
                            <span class="font-medium text-gray-900">
                                {{ $order->shipping_cost > 0 ? 'R$ ' . number_format($order->shipping_cost, 2, ',', '.') : 'Grátis' }}
                            </span>
                        </div>

                        {{-- 5. Total Final --}}
                        <div class="flex justify-between items-center pt-4 mt-3 -mx-6 px-6 py-4">
                            <div>
                                <span class="font-bold text-lg text-gray-900 uppercase tracking-tight">Total Pago</span>
                            </div>
                            <span class="font-black text-2xl text-black">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>

        </div>

        {{-- Botão de Ação Inferior (Mobile) - Exibido no fim da página em ecrãs pequenos --}}
        <div class="mt-8 block lg:hidden">
            <a href="{{ route('profile.orders') }}" class="w-full inline-flex justify-center items-center py-4 border border-black rounded-xl text-base font-bold uppercase tracking-widest text-white bg-black hover:bg-white hover:text-black transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                Ver Todos os Pedidos
            </a>
        </div>

    </div>
</div>