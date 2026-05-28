{{-- resources/views/profile/order.blade.php --}}
<x-profile.layout>
    {{-- ✏️ alterado: Injeção do estado Alpine.js para o modal de avaliação --}}
    <div class="max-w-5xl mx-auto px-4 pt-8 pb-20" x-data="{ 
        reviewModalOpen: false, activeProductId: null, activeProductName: '', 
        rating: 5, comment: '', existingImages: [], hoverRating: 0, removedImages: [],
        openReviewModal(id, name, review) {
            this.activeProductId = id; 
            this.activeProductName = name;
            this.removedImages = [];
            if(review) { 
                this.rating = review.rating; 
                this.comment = review.comment; 
                this.existingImages = review.images || []; 
            } else { 
                this.rating = 5; 
                this.comment = ''; 
                this.existingImages = []; 
            }
            this.reviewModalOpen = true;
        }
    }">
        
        {{-- CABEÇALHO DA PÁGINA --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 mb-10 pb-6">
            <div>
                <a href="{{ route('profile.orders') }}" class="text-xs font-black uppercase tracking-[0.2em] text-gray-500 hover:text-black transition-colors mb-4 inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Voltar para Meus Pedidos
                </a>
                <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900 mt-2">
                    Pedido #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                </h1>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-2">
                    Realizado em {{ $order->created_at->format('d/m/Y \à\s H:i') }}
                </p>
            </div>
            
            @php
                $statusClasses = match($order->status) {
                    'pending' => 'text-yellow-700 border-yellow-500 bg-yellow-50',
                    'paid', 'preparing', 'shipped', 'delivered' => 'text-green-700 border-green-600 bg-green-50',
                    'canceled', 'refunded' => 'text-red-700 border-red-600 bg-red-50',
                    default => 'text-gray-700 border-gray-400 bg-gray-50'
                };
            @endphp
            <span class="px-5 py-2 text-xs font-black uppercase tracking-[0.2em] border-2 {{ $statusClasses }} rounded-none w-full sm:w-auto text-center flex-shrink-0">
                {{ $order->status_label }}
            </span>
        </div>

        <div class="space-y-8">
            
            {{-- SEÇÃO 1: ACOMPANHAMENTO DO PEDIDO --}}
            <section class="bg-white border border-gray-300 p-6 sm:p-8 rounded-none">
                <h2 class="text-xl font-black uppercase tracking-tight text-gray-900 mb-8">Acompanhamento do Pedido</h2>
                
                <div class="relative px-2">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-between">
                        @php
                            $steps = [
                                ['label' => 'Realizado', 'icon' => '<path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />', 'status' => 'pending'],
                                ['label' => 'Pagamento', 'icon' => '<path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />', 'status' => 'paid'],
                                ['label' => 'Separação', 'icon' => '<path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />', 'status' => 'preparing'],
                                ['label' => 'Enviado', 'icon' => '<path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />', 'status' => 'shipped'],
                                ['label' => 'Entregue', 'icon' => '<path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />', 'status' => 'delivered']
                            ];
                            $orderStepNames = array_column($steps, 'status');
                            $currentIndex = array_search($order->status, $orderStepNames);
                            if($order->status == 'canceled') $currentIndex = -1;
                        @endphp

                        @foreach($steps as $index => $step)
                            <div class="flex flex-col items-center">
                                <div class="h-10 w-10 sm:h-12 sm:w-12 flex items-center justify-center border-2 transition-all duration-300 relative z-10 rounded-none {{ $currentIndex >= $index ? 'bg-green-600 border-green-600 text-white' : 'bg-white border-gray-200 text-gray-300' }}">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $step['icon'] !!}
                                    </svg>
                                </div>
                                <span class="mt-4 text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-center {{ $currentIndex >= $index ? 'text-green-700' : 'text-gray-400' }}">
                                    {{ $step['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- SEÇÃO 2: ITENS DO PEDIDO --}}
            <section class="bg-white border border-gray-300 p-6 sm:p-8 rounded-none">
                <h2 class="text-xl font-black uppercase tracking-tight text-gray-900 mb-6">Itens do Pedido</h2>
                <div class="flex flex-col">
                    @foreach($order->items as $item)
                        <div class="py-6 first:pt-0 last:pb-0 flex gap-4 sm:gap-6 items-start sm:items-center border-b border-gray-100 last:border-0">
                            
                            {{-- Foto do Item --}}
                            <div class="w-20 h-24 sm:w-24 sm:h-28 min-w-[5rem] sm:min-w-[6rem] bg-white border border-gray-200 overflow-hidden flex-shrink-0 flex items-center justify-center p-2 rounded-none">
                                @php
                                    $imagePath = null;
                                    $cleanImg = function($img) {
                                        if (is_string($img) && str_starts_with($img, '["')) {
                                            $dec = json_decode($img, true);
                                            return (is_array($dec) && count($dec) > 0) ? $dec[0] : $img;
                                        }
                                        return $img;
                                    };
                                    if ($item->variant) {
                                        $imagePath = $cleanImg($item->variant->image_url ?: $item->variant->image);
                                        if (!$imagePath && $item->product && $item->product->variants) {
                                            $opts = is_string($item->variant->options) ? json_decode($item->variant->options, true) : $item->variant->options;
                                            $minhaCor = null;
                                            if (is_array($opts)) {
                                                foreach ($opts as $key => $val) {
                                                    if (stripos(trim($key), 'cor') !== false || stripos(trim($key), 'color') !== false) {
                                                        $minhaCor = trim($val); break;
                                                    }
                                                }
                                            }
                                            if ($minhaCor) {
                                                $irma = $item->product->variants->first(function($v) use ($minhaCor) {
                                                    $vOpts = is_string($v->options) ? json_decode($v->options, true) : $v->options;
                                                    $corIrma = null;
                                                    if (is_array($vOpts)) {
                                                        foreach ($vOpts as $key => $val) {
                                                            if (stripos(trim($key), 'cor') !== false || stripos(trim($key), 'color') !== false) {
                                                                $corIrma = trim($val); break;
                                                            }
                                                        }
                                                    }
                                                    return (strcasecmp((string)$corIrma, (string)$minhaCor) === 0) && (!empty($v->image) || !empty($v->image_url));
                                                });
                                                if ($irma) $imagePath = $cleanImg($irma->image_url ?: $irma->image);
                                            }
                                        }
                                    }
                                    $imagePath = $imagePath ?: $item->product?->image_url;
                                @endphp
                                @if($imagePath)
                                    <img src="{{ Storage::url($imagePath) }}" class="w-full h-full object-contain">
                                @endif
                            </div>
                            
                            {{-- Container Flexível para Detalhes e Subtotal --}}
                            <div class="flex-1 min-w-0 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-6">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('shop.product', $item->product->slug) }}" class="font-black text-sm sm:text-base uppercase tracking-tight text-gray-900 truncate hover:text-gray-600 transition-colors">
                                        {{ $item->product->name }}
                                    </a>
                                    
                                    @if($item->variant)
                                        @php
                                            $options = is_string($item->variant->options) ? json_decode($item->variant->options, true) : $item->variant->options;
                                        @endphp
                                        @if(!empty($options))
                                            <p class="text-[10px] sm:text-xs uppercase font-bold text-gray-500 mt-1 tracking-widest truncate">
                                                {{ implode(' / ', $options) }}
                                            </p>
                                        @endif
                                    @endif
                                    
                                    <p class="text-xs font-bold text-gray-500 mt-2 uppercase tracking-widest">
                                        Qtd: <span class="text-gray-900">{{ $item->quantity }}</span> <span class="mx-2">x</span> R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                                    </p>
                                </div>
                                
                                <div class="text-left sm:text-right flex-shrink-0 mt-1 sm:mt-0 space-y-3">
                                    <p class="font-black text-base sm:text-lg text-gray-900">R$ {{ number_format($item->total, 2, ',', '.') }}</p>
                                    
                                    {{-- ✏️ alterado: Injeta os dados dinâmicos da avaliação para Edição/Criação --}}
                                    @if($order->status === 'delivered')
                                        @php 
                                            // Consulta direta e segura para evitar LazyLoadingViolationException
                                            $review = \App\Models\Review::where('user_id', auth()->id())
                                                ->where('product_id', $item->product_id)
                                                ->first(); 
                                                
                                            $reviewData = $review ? json_encode(['rating' => $review->rating, 'comment' => $review->comment, 'images' => $review->images ?? []]) : 'null';
                                        @endphp
                                        <button type="button" 
                                                @click="openReviewModal('{{ $item->product_id }}', '{{ addslashes($item->product->name) }}', {{ $reviewData }})"
                                                class="w-full sm:w-auto block text-center px-4 py-2 border border-black rounded-none bg-black text-white font-black text-[10px] tracking-widest uppercase hover:bg-white hover:text-black transition-colors cursor-pointer">
                                            {{ $review ? 'Editar Avaliação' : 'Avaliar Produto' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- GRID: DADOS DO COMPRADOR & INFORMAÇÕES DE ENTREGA --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- Dados do Comprador --}}
                <section class="bg-white border border-gray-300 p-6 sm:p-8 rounded-none">
                    <h2 class="text-xl font-black uppercase tracking-tight text-gray-900 mb-6">Dados do Comprador</h2>
                    <div class="flex flex-col">
                        <div class="pb-6">
                            <p class="text-xs font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Nome Completo</p>
                            <p class="text-sm font-bold text-gray-900 uppercase tracking-widest">{{ $order->customer_first_name }} {{ $order->customer_last_name }}</p>
                        </div>
                        <div class="py-6">
                            <p class="text-xs font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Documento (CPF)</p>
                            @php
                                $cpf = preg_replace('/[^0-9]/', '', $order->customer_cpf ?? '');
                                $formattedCpf = strlen($cpf) === 11 ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf) : ($order->customer_cpf ?? 'Não Informado');
                            @endphp
                            <p class="text-sm font-bold text-gray-900 uppercase tracking-widest">{{ $formattedCpf }}</p>
                        </div>
                        <div class="pt-6">
                            <p class="text-xs font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Telefone</p>
                            @php
                                $phone = preg_replace('/[^0-9]/', '', $order->customer_phone ?? '');
                                if (strlen($phone) === 11) {
                                    $formattedPhone = preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
                                } elseif (strlen($phone) === 10) {
                                    $formattedPhone = preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
                                } else {
                                    $formattedPhone = $order->customer_phone ?? 'Não Informado';
                                }
                            @endphp
                            <p class="text-sm font-bold text-gray-900 uppercase tracking-widest">{{ $formattedPhone }}</p>
                        </div>
                    </div>
                </section>

                {{-- Informações da Entrega --}}
                <section class="bg-white border border-gray-300 p-6 sm:p-8 rounded-none">
                    <h2 class="text-xl font-black uppercase tracking-tight text-gray-900 mb-6">Informações da Entrega</h2>
                    <div class="flex flex-col h-full">
                        <div class="pb-6">
                            <p class="text-xs font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Endereço de Destino</p>
                            <p class="text-sm font-bold text-gray-800 uppercase tracking-widest leading-relaxed">
                                {{ $order->address_json['street'] ?? '' }}, {{ $order->address_json['number'] ?? '' }}<br>
                                @if(!empty($order->address_json['complement'])) {{ $order->address_json['complement'] }} - @endif
                                {{ $order->address_json['neighborhood'] ?? '' }}<br>
                                {{ $order->address_json['city'] ?? '' }}/{{ $order->address_json['state'] ?? '' }} - CEP: 
                                @php
                                    $cep = preg_replace('/[^0-9]/', '', $order->address_json['zip_code'] ?? '');
                                    $formattedCep = strlen($cep) === 8 ? preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep) : ($order->address_json['zip_code'] ?? '');
                                @endphp
                                {{ $formattedCep }}
                            </p>
                        </div>
                        <div class="pt-6">
                            <p class="text-xs font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Método e Custo</p>
                            <p class="text-sm font-bold text-gray-900 uppercase tracking-widest">{{ $order->shipping_method ?? 'Correios' }}</p>
                            <p class="text-sm font-black text-gray-900 mt-2">FRETE: R$ {{ number_format($order->shipping_cost, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </section>

                {{-- Rastreio --}}
                <section class="bg-white border border-gray-300 p-6 sm:p-8 rounded-none flex flex-col justify-between">
                    <h2 class="text-xl font-black uppercase tracking-tight text-gray-900 mb-6">Rastreio</h2>
                    @if($order->tracking_code)
                        <div class="flex flex-col gap-4 mt-auto">
                            <div>
                                <p class="text-xs font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Código do Objeto</p>
                                <p class="text-lg font-black text-gray-900 uppercase tracking-widest">{{ $order->tracking_code }}</p>
                            </div>
                            <a href="https://rastreamento.correios.com.br/app/index.php" target="_blank" class="w-full text-center px-8 py-3 bg-black text-white border-2 border-black font-black uppercase tracking-widest text-[10px] hover:bg-white hover:text-black transition-colors cursor-pointer rounded-none mt-2">
                                Rastrear no Site
                            </a>
                        </div>
                    @else
                        <div class="mt-auto">
                            <p class="text-xs font-black text-gray-500 uppercase tracking-widest">
                                Aguardando despacho para gerar código.
                            </p>
                        </div>
                    @endif
                </section>

                {{-- Nota Fiscal --}}
                <section class="bg-white border border-gray-300 p-6 sm:p-8 rounded-none flex flex-col justify-between">
                    <h2 class="text-xl font-black uppercase tracking-tight text-gray-900 mb-6">Nota Fiscal</h2>
                    @if($order->nf_url || $order->nf_code)
                        <div class="flex flex-col gap-4 mt-auto">
                            <div>
                                <p class="text-xs font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Número da NF</p>
                                <p class="text-lg font-black text-gray-900 uppercase tracking-widest">{{ $order->nf_code ?? 'Disponível no link' }}</p>
                            </div>
                            @if($order->nf_url)
                                <a href="{{ $order->nf_url }}" target="_blank" class="w-full text-center px-8 py-3 bg-black text-white border-2 border-black font-black uppercase tracking-widest text-[10px] hover:bg-white hover:text-black transition-colors cursor-pointer rounded-none mt-2">
                                    Visualizar NF-e
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="mt-auto">
                            <p class="text-xs font-black text-gray-500 uppercase tracking-widest">
                                A nota fiscal será gerada após o faturamento.
                            </p>
                        </div>
                    @endif
                </section>
                
            </div>

            {{-- SEÇÃO 3: RESUMO FINANCEIRO --}}
            <section class="bg-white border border-gray-300 p-6 sm:p-8 rounded-none">
                <h2 class="text-xl font-black uppercase tracking-tight text-gray-900 mb-6 pb-4">Resumo Financeiro</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center text-sm font-bold uppercase tracking-widest text-gray-500">
                        <span>Método de Pagamento</span>
                        <span class="text-gray-900">
                            @if($order->payment_method == 'pix') PIX @elseif($order->payment_method == 'credit_card') Cartão de Crédito @else Boleto @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm font-bold uppercase tracking-widest text-gray-500">
                        <span>Subtotal de Produtos</span>
                        <span class="text-gray-900">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm font-bold uppercase tracking-widest text-gray-500">
                        <span>Valor do Frete</span>
                        <span class="text-gray-900">R$ {{ number_format($order->shipping_cost, 2, ',', '.') }}</span>
                    </div>
                    
                    @if(isset($order->promotional_discount) && $order->promotional_discount > 0)
                        <div class="flex justify-between items-center text-sm font-black uppercase tracking-widest text-green-600">
                            <span>Desconto Promocional</span>
                            <span>- R$ {{ number_format($order->promotional_discount, 2, ',', '.') }}</span>
                        </div>
                    @endif

                    @if(isset($order->coupon_discount) && $order->coupon_discount > 0)
                        <div class="flex justify-between items-center text-sm font-black uppercase tracking-widest text-green-600">
                            <span>Cupom Aplicado {{ !empty($order->coupon_code) ? '(' . $order->coupon_code . ')' : '' }}</span>
                            <span>- R$ {{ number_format($order->coupon_discount, 2, ',', '.') }}</span>
                        </div>
                    @endif

                    @if(isset($order->discount) && $order->discount > 0 && empty($order->promotional_discount) && empty($order->coupon_discount))
                        <div class="flex justify-between items-center text-sm font-black uppercase tracking-widest text-green-600">
                            <span>Descontos</span>
                            <span>- R$ {{ number_format($order->discount, 2, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-end pt-6 mt-2">
                        <span class="text-sm font-black uppercase tracking-[0.2em] text-gray-500">Total Pago</span>
                        <span class="text-3xl font-black text-gray-900">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
                    </div>
                </div>
            </section>  

            {{-- SEÇÃO 4: AÇÕES FINAIS --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-12">
                <button type="button" x-data @click="$dispatch('open-cart'); $dispatch('reorder-cart', { order: {{ $order->id }} })" 
                        class="w-full bg-black text-white border-2 border-black font-black uppercase tracking-widest text-[10px] sm:text-xs py-4 hover:bg-white hover:text-black transition-colors rounded-none cursor-pointer">
                    Repetir Compra
                </button>
                
                <a href="{{ route('help', ['order' => $order->id]) }}" class="w-full text-center block bg-black text-white border-2 border-black font-black uppercase tracking-widest text-[10px] sm:text-xs py-4 hover:bg-white hover:text-black transition-colors rounded-none cursor-pointer">
                    Preciso de Ajuda
                </a>
            </div>
        </div>

      {{-- FORMULÁRIO MODAL DE AVALIAÇÃO --}}
<div x-show="reviewModalOpen" 
     class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto bg-opacity-60 backdrop-blur-sm p-4"
     style="display: none;"
     x-transition>
    
    <div class="bg-white border border-gray-300 w-full max-w-lg p-6 sm:p-8 rounded-none relative shadow-xl" @click.away="reviewModalOpen = false">
        
        <button type="button" @click="reviewModalOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 font-light text-2xl focus:outline-none">✕</button>
        
        <h3 class="text-lg font-black uppercase tracking-tight text-gray-900 mb-1">Avaliar Produto</h3>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-6 truncate" x-text="activeProductName"></p>

        <form :action="'/minha-conta/produtos/' + activeProductId + '/avaliar'" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            {{-- Campo Oculto para remover imagens --}}
            <input type="hidden" name="removed_images" :value="JSON.stringify(removedImages)">

            <div>
                <label class="text-xs font-black uppercase text-gray-500 block mb-2">Sua Nota *</label>
                <input type="hidden" name="rating" :value="rating">
                <div class="flex gap-1 text-gray-300">
                    <template x-for="i in 5">
                        <button type="button" @click="rating = i" @mouseenter="hoverRating = i" @mouseleave="hoverRating = 0"
                                class="focus:outline-none text-3xl cursor-pointer transition-colors duration-200" 
                                :style="(hoverRating ? hoverRating >= i : rating >= i) ? 'color: #facc15' : 'color: #d1d5db'">★</button>
                    </template>
                </div>
            </div>

            <div>
                <label for="comment" class="text-xs font-black uppercase text-gray-500 block mb-2">Depoimento</label>
                <textarea id="comment" name="comment" x-model="comment" rows="4" maxlength="1000" class="w-full border border-gray-300 p-3 rounded-none focus:border-black focus:ring-0"></textarea>
            </div>

            {{-- ✏️ SEÇÃO DE UPLOAD ATUALIZADA --}}
            <div x-data="{ fileCount: 0 }">
                <label class="text-xs font-black uppercase text-gray-500 block mb-2">Fotos</label>
                
                {{-- Botão estilizado simulando o input --}}
                <label for="image-upload" class="flex items-center justify-center w-full bg-white text-black border border-black font-black text-xs uppercase tracking-widest h-12 hover:bg-black hover:text-white transition-colors cursor-pointer rounded-none">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Procurar Imagens
                </label>
                
                <input id="image-upload" type="file" name="images[]" multiple accept="image/jpeg, image/png, image/webp" class="hidden" @change="fileCount = $event.target.files.length">
                
                <p x-show="fileCount > 0" style="display: none;" class="text-[10px] font-bold text-green-600 mt-2 uppercase tracking-widest">
                    <span x-text="fileCount"></span> arquivo(s) novo(s) selecionado(s)
                </p>
                
                {{-- Container reativo de imagens existentes --}}
                <div x-show="existingImages.length > 0" class="flex gap-3 flex-wrap mt-3">
                    <template x-for="img in existingImages" :key="img">
                        <div class="relative w-16 h-16 border border-gray-200 bg-gray-50" x-show="!removedImages.includes(img)">
                            <img :src="'/storage/' + img" class="w-full h-full object-cover">
                            <button type="button" @click="removedImages.push(img)" class="cursor-pointer absolute -top-1.5 -right-1.5 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-black shadow hover:bg-red-700 transition-colors focus:outline-none" title="Remover imagem">✕</button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <button type="button" @click="reviewModalOpen = false" class="cursor-pointer w-full bg-red-600 text-white border border-red-600 font-black text-xs uppercase tracking-widest h-12 hover:bg-white hover:text-red-600 transition-colors duration-200 rounded-none focus:outline-none">Cancelar</button>
                <button type="submit" class="cursor-pointer w-full bg-black text-white border border-black font-black text-xs uppercase tracking-widest h-12 hover:bg-white hover:text-black transition-colors rounded-none">Salvar</button>
            </div>
        </form>
    </div>
</div>
        </div>
    </div>
</x-profile.layout>