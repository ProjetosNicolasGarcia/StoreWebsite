{{-- resources/views/profile/orders.blade.php --}}
<x-profile.layout>
    <div class="max-w-5xl mx-auto px-4 pt-8 pb-20">
        <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900 mb-8">Meus Pedidos</h1>

        @if($orders->isEmpty())
            <div class="text-center py-16 border border-gray-200 bg-white">
                <p class="text-gray-400 uppercase tracking-[0.2em] font-black text-xs">Nenhum pedido encontrado.</p>
                <a href="{{ route('shop.search') }}" class="mt-6 inline-block bg-black text-white border-2 border-black px-10 py-3 rounded-none uppercase font-black tracking-widest text-xs hover:bg-white hover:text-black transition-colors duration-300 cursor-pointer">
                    Ir às compras
                </a>
            </div>
        @else
            <div class="space-y-6">
                @foreach($orders as $order)
                    <div class="bg-white border border-gray-300 rounded-none flex flex-col w-full overflow-hidden">
                        
                        {{-- CABEÇALHO --}}
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 p-4 sm:p-6 border-b border-gray-200">
                            <div>
                                <span class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Pedido</span>
                                <span class="font-black text-lg sm:text-xl text-gray-900 uppercase tracking-tight">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            
                            @php
                                $statusClasses = match($order->status) {
                                    'pending' => 'text-yellow-700 border-yellow-500 bg-yellow-50',
                                    'paid', 'preparing' => 'text-green-600 border-green-500 bg-green-50',
                                    'shipped', 'delivered' => 'text-green-700 border-green-600 bg-green-50', // Verde levemente mais escuro
                                    'canceled', 'refunded' => 'text-red-700 border-red-600 bg-red-50',
                                    default => 'text-gray-700 border-gray-400 bg-gray-50'
                                };
                            @endphp
                            <span class="px-4 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] border-2 {{ $statusClasses }} w-full sm:w-auto text-center flex-shrink-0">
                                {{ $order->status_label }}
                            </span>
                        </div>

                        {{-- CORPO --}}
                        <div class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                            @foreach($order->items as $item)
                                <div class="flex gap-4 items-start">
                                    {{-- Imagem do Produto --}}
                                    <div class="w-20 h-24 bg-white border border-gray-200 overflow-hidden flex-shrink-0 flex items-center justify-center p-2">
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
                                                $imagePath = $cleanImg($item->variant->image);
                                                if (!$imagePath && $item->product->variants) {
                                                    $opts = is_string($item->variant->options) ? json_decode($item->variant->options, true) : $item->variant->options;
                                                    $minCor = null;
                                                    if(is_array($opts)) foreach($opts as $k => $v) if(stripos($k, 'cor') !== false) $minCor = $v;
                                                    if ($minCor) {
                                                        $irma = $item->product->variants->first(function($v) use ($minCor) {
                                                            $vO = is_string($v->options) ? json_decode($v->options, true) : $v->options;
                                                            $cI = null;
                                                            if(is_array($vO)) foreach($vO as $kk => $vv) if(stripos($kk, 'cor') !== false) $cI = $vv;
                                                            return $v->image && $cI === $minCor;
                                                        });
                                                        if($irma) $imagePath = $cleanImg($irma->image);
                                                    }
                                                }
                                            }
                                            $imagePath = $imagePath ?: $item->product?->image_url;
                                        @endphp
                                        <img src="{{ Storage::url($imagePath) }}" class="w-full h-full object-contain">
                                    </div>
                                    
                                    {{-- Textos e Preço do Produto (Flex-col no Mobile, Flex-row no Desktop) --}}
                                    <div class="flex-1 min-w-0 flex flex-col sm:flex-row justify-between gap-3 sm:gap-4 sm:items-center">
                                        <div class="flex-1 min-w-0">
                                            {{-- Removido o truncate, adicionado leading-snug para quebra de linha elegante --}}
                                            <h4 class="font-black text-sm uppercase tracking-tight text-gray-900 leading-snug">{{ $item->product->name }}</h4>
                                            @if($item->variant)
                                                @php
                                                    $rawOpts = $item->variant->options;
                                                    $optsArray = is_string($rawOpts) ? json_decode($rawOpts, true) : $rawOpts;
                                                    $optsArray = is_array($optsArray) ? $optsArray : (is_string($rawOpts) && !empty(trim($rawOpts)) ? [$rawOpts] : []);
                                                @endphp
                                                @if(!empty($optsArray))
                                                    <p class="text-[10px] sm:text-xs uppercase font-bold text-gray-500 mt-1 tracking-widest">{{ implode(' / ', $optsArray) }}</p>
                                                @endif
                                            @endif
                                            <p class="text-[10px] sm:text-xs font-bold text-gray-400 mt-1 uppercase tracking-widest">Qtd: <span class="text-gray-900">{{ $item->quantity }}</span></p>
                                        </div>

                                        {{-- Preço cai para a linha debaixo em telas pequenas --}}
                                        <div class="text-left sm:text-right flex-shrink-0 mt-1 sm:mt-0">
                                            <p class="font-black text-base text-gray-900">R$ {{ number_format($item->unit_price * $item->quantity, 2, ',', '.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- RODAPÉ --}}
                        <div class="bg-white border-t border-gray-200 p-4 sm:p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div class="w-full md:w-auto flex justify-between md:justify-start items-center gap-4">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Total</span>
                                <span class="text-xl font-black text-gray-900">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
                            </div>
                            
                            {{-- Botões mais finos (py-3) e em coluna no mobile extremo --}}
                            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                <a href="{{ route('profile.order.show', $order->id) }}" class="w-full sm:w-auto flex-1 md:flex-none text-center bg-black text-white border-2 border-black px-6 py-3 rounded-none uppercase font-black tracking-widest text-[10px] sm:text-xs hover:bg-white hover:text-black transition-colors duration-300 cursor-pointer">
                                    Detalhes
                                </a>
                                <button type="button" x-data @click="$dispatch('open-cart'); $dispatch('reorder-cart', { order: {{ $order->id }} })" 
                                    class="w-full sm:w-auto flex-1 md:flex-none text-center bg-black text-white border-2 border-black px-6 py-3 rounded-none uppercase font-black tracking-widest text-[10px] sm:text-xs hover:bg-white hover:text-black transition-colors duration-300 cursor-pointer">
                                    Repetir Pedido
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- PAGINAÇÃO ULTRA MINIMALISTA --}}
            <div class="mt-12 pagination-pt">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <style>
        /* Ocultar textos e menu mobile */
        .pagination-pt nav p,
        .pagination-pt nav > div:first-of-type {
            display: none !important;
        }
        
        /* Centralizar menu desktop */
        .pagination-pt nav > div:last-of-type {
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
        }
        
        /* Item ativo (Página atual) */
        .pagination-pt nav span[aria-current="page"] > span {
            background-color: #000000 !important;
            color: #ffffff !important;
            border: 2px solid #000000 !important;
            border-radius: 0 !important;
            font-weight: 900 !important;
            box-shadow: none !important;
        }
        
        /* Itens inativos - Removendo todas as bordas e caixas */
        .pagination-pt nav a, 
        .pagination-pt nav span {
            border: none !important;
            background-color: transparent !important;
            color: #6b7280 !important;
            box-shadow: none !important;
            font-weight: 900 !important;
            border-radius: 0 !important;
            padding: 0.5rem 1rem !important;
        }
        
        /* Hover suave apenas mudando a cor do texto */
        .pagination-pt nav a:hover {
            color: #000000 !important;
            background-color: transparent !important;
        }
        
        .pagination-pt nav a:focus, 
        .pagination-pt nav button:focus {
            outline: 2px solid #000000 !important;
            outline-offset: 2px !important;
            background-color: transparent !important;
        }

        /* Ajuste do ícone SVG das setas */
        .pagination-pt svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
        }
    </style>
</x-profile.layout>