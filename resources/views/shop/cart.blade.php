<x-layout>
    {{-- Container Principal: Espaçamento superior ajustado para acomodar o Header fixo --}}
    <div class="container mx-auto px-4 py-8 pt-32" aria-labelledby="cart-title">
        <h1 id="cart-title" class="text-3xl font-black uppercase tracking-tighter mb-8">Seu Carrinho</h1>

        {{-- Verificação: O carrinho possui itens? --}}
        @if(isset($items) && $items->count() > 0)
            <div class="bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden" role="region" aria-label="Lista de produtos no carrinho">
                <table class="w-full text-left">
                    {{-- Cabeçalho da Tabela --}}
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th scope="col" class="p-4 text-xs font-bold text-gray-500 uppercase">Produto</th>
                            <th scope="col" class="p-4 text-xs font-bold text-gray-500 uppercase text-center">Qtd</th>
                            <th scope="col" class="p-4 text-xs font-bold text-gray-500 uppercase text-right">Preço</th>
                            <th scope="col" class="p-4 text-xs font-bold text-gray-500 uppercase text-right">Subtotal</th>
                            <th scope="col" class="p-4"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>

                    {{-- Listagem de Itens --}}
                    <tbody class="divide-y divide-gray-100">
                        @foreach($items as $item)
                            <tr class="group hover:bg-gray-50/50 transition">
                                <td class="p-4">
                                    <div class="flex items-center gap-4">
                                        {{-- 
                                            LÓGICA DE IMAGEM: 
                                            Prioriza a imagem da variante (ex: camisa azul) sobre a imagem padrão do produto.
                                        --}}
                                        @php
                                            $img = $item->product->image_url;
                                            if ($item->variant && $item->variant->image) {
                                                $img = $item->variant->image;
                                            }
                                        @endphp
                                        
                                        {{-- Thumbnail do Produto --}}
                                        <div class="w-16 h-20 shrink-0 bg-white border border-gray-200 rounded overflow-hidden" aria-hidden="true">
                                            <img src="{{ Storage::url($img) }}" alt="" class="w-full h-full object-cover">
                                        </div>

                                        {{-- Detalhes do Produto --}}
                                        <div>
                                            <a href="{{ route('shop.product', $item->product->slug) }}" class="font-bold text-gray-900 uppercase text-sm hover:underline" aria-label="Ver detalhes de {{ $item->product->name }}">
                                                {{ $item->product->name }}
                                            </a>
                                            
                                            {{-- Renderização de Atributos: Exibe Cor, Tamanho, etc. --}}
                                            @if($item->variant && is_array($item->variant->options))
                                                <div class="flex flex-wrap gap-1 mt-1" aria-label="Variantes escolhidas">
                                                    @foreach($item->variant->options as $key => $value)
                                                        <span class="text-[10px] uppercase font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                                                            <span class="sr-only">{{ $key }}:</span> {{ $value }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            {{-- Identificador Único (SKU) --}}
                                            @if($item->variant)
                                                <div class="text-[10px] text-gray-400 mt-0.5" aria-label="Código SKU">SKU: {{ $item->variant->sku }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Controle de Quantidade: Botões de Incrementar/Decrementar --}}
                                <td class="p-4 text-center">
                                    <div class="inline-flex items-center border rounded-lg h-8" role="group" aria-label="Ajustar quantidade para {{ $item->product->name }}">
                                        <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="action" value="decrease">
                                            <button type="submit" aria-label="Diminuir quantidade de {{ $item->product->name }}" class="px-2 text-gray-500 hover:text-black hover:bg-gray-100 h-full rounded-l-lg focus:outline-none focus:ring-2 focus:ring-black">-</button>
                                        </form>

                                        <span class="px-2 text-sm font-bold" aria-live="polite" aria-label="Quantidade atual">{{ $item->quantity }}</span>

                                        <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="action" value="increase">
                                            <button type="submit" aria-label="Aumentar quantidade de {{ $item->product->name }}" class="px-2 text-gray-500 hover:text-black hover:bg-gray-100 h-full rounded-r-lg focus:outline-none focus:ring-2 focus:ring-black">+</button>
                                        </form>
                                    </div>
                                </td>

                                {{-- Preço Unitário Dinâmico --}}
                                <td class="p-4 text-right">
                                    @php
                                        // Lógica de Preço: Variante > Preço Promoção > Preço Base
                                        $price = $item->variant ? $item->variant->final_price : ($item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price);
                                    @endphp
                                    <span class="font-bold text-sm" aria-label="Preço unitário">R$ {{ number_format($price, 2, ',', '.') }}</span>
                                </td>

                                {{-- Subtotal do Item --}}
                                <td class="p-4 text-right font-black text-sm" aria-label="Subtotal do item">
                                    R$ {{ number_format($price * $item->quantity, 2, ',', '.') }}
                                </td>

                                {{-- Botão de Remover Item --}}
                                <td class="p-4 text-right">
                                    <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600 transition-colors focus:outline-none focus:ring-2 focus:ring-red-600 rounded" title="Remover item" aria-label="Remover {{ $item->product->name }} do carrinho">
                                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                {{-- Rodapé do Carrinho: Totalização e CTA (Call to Action) --}}
                <div class="bg-gray-50 p-6 flex flex-col md:flex-row justify-between items-center border-t border-gray-100 gap-4" role="contentinfo" aria-label="Resumo e total do carrinho">
                    <div>
                        <p class="text-sm text-gray-500">Frete calculado no checkout</p>
                        <p class="text-2xl font-black text-gray-900 mt-1" aria-live="polite">Total: R$ {{ number_format($total, 2, ',', '.') }}</p>
                    </div>
                    
                    {{-- Lógica de Autenticação para Finalização --}}
                    @auth
                        <a href="{{ url('/checkout') }}" aria-label="Prosseguir para finalizar a compra" class="w-full md:w-auto bg-black text-white px-8 py-4 rounded-xl font-bold uppercase tracking-widest hover:bg-gray-800 transition shadow-lg text-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                            Finalizar Compra
                        </a>
                    @else
                        <a href="{{ route('login') }}" aria-label="Fazer login para prosseguir com a compra" class="w-full md:w-auto bg-black text-white px-8 py-4 rounded-xl font-bold uppercase tracking-widest hover:bg-gray-800 transition shadow-lg text-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                            Login para Comprar
                        </a>
                    @endauth
                </div>
            </div>
        @else
            {{-- Estado Vazio (Empty State) --}}
            <div class="text-center py-20 border-2 border-dashed border-gray-200 rounded-xl" role="status" aria-live="polite">
                <p class="text-gray-400 text-lg mb-4">Seu carrinho está vazio.</p>
                <a href="{{ route('home') }}" aria-label="Voltar para a página inicial e continuar comprando" class="text-black font-bold border-b-2 border-black hover:text-gray-600 hover:border-gray-600 transition focus:outline-none focus:ring-2 focus:ring-black">
                    Ir às compras
                </a>
            </div>
        @endif
    </div>
</x-layout>