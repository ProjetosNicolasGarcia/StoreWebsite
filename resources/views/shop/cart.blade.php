<x-layout>
    <div class="container mx-auto px-4 py-8 pt-32">
        <h1 class="text-3xl font-black uppercase tracking-tighter mb-8">Seu Carrinho</h1>

        @if(isset($items) && $items->count() > 0)
            <div class="bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase">Produto</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase text-center">Qtd</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase text-right">Preço</th>
                            <th class="p-4 text-xs font-bold text-gray-500 uppercase text-right">Subtotal</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($items as $item)
                            <tr class="group hover:bg-gray-50/50 transition">
                                <td class="p-4">
                                    <div class="flex items-center gap-4">
                                        {{-- Lógica Inteligente de Imagem --}}
                                        @php
                                            $img = $item->product->image_url;
                                            if ($item->variant && $item->variant->image) {
                                                $img = $item->variant->image;
                                            }
                                        @endphp
                                        
                                        <div class="w-16 h-20 shrink-0 bg-white border border-gray-200 rounded overflow-hidden">
                                            <img src="{{ Storage::url($img) }}" class="w-full h-full object-cover">
                                        </div>

                                        <div>
                                            <a href="{{ route('shop.product', $item->product->slug) }}" class="font-bold text-gray-900 uppercase text-sm hover:underline">
                                                {{ $item->product->name }}
                                            </a>
                                            
                                            {{-- Renderiza os Atributos (Cor, Tamanho) --}}
                                            @if($item->variant && is_array($item->variant->options))
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    @foreach($item->variant->options as $key => $value)
                                                        <span class="text-[10px] uppercase font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                                                            {{ $key }}: {{ $value }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            {{-- SKU --}}
                                            @if($item->variant)
                                                <div class="text-[10px] text-gray-400 mt-0.5">SKU: {{ $item->variant->sku }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="inline-flex items-center border rounded-lg h-8">
                                        <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="action" value="decrease">
                                            <button class="px-2 text-gray-500 hover:text-black hover:bg-gray-100 h-full rounded-l-lg">-</button>
                                        </form>
                                        <span class="px-2 text-sm font-bold">{{ $item->quantity }}</span>
                                        <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="action" value="increase">
                                            <button class="px-2 text-gray-500 hover:text-black hover:bg-gray-100 h-full rounded-r-lg">+</button>
                                        </form>
                                    </div>
                                </td>

                                <td class="p-4 text-right">
                                    @php
                                        // Pega o preço da variante OU do produto
                                        $price = $item->variant ? $item->variant->final_price : ($item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price);
                                    @endphp
                                    <span class="font-bold text-sm">R$ {{ number_format($price, 2, ',', '.') }}</span>
                                </td>

                                <td class="p-4 text-right font-black text-sm">
                                    R$ {{ number_format($price * $item->quantity, 2, ',', '.') }}
                                </td>

                                <td class="p-4 text-right">
                                    <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="text-red-400 hover:text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                {{-- Rodapé do Carrinho --}}
                <div class="bg-gray-50 p-6 flex justify-between items-center border-t border-gray-100">
                    <div>
                        <p class="text-sm text-gray-500">Frete calculado no checkout</p>
                        <p class="text-2xl font-black text-gray-900 mt-1">Total: R$ {{ number_format($total, 2, ',', '.') }}</p>
                    </div>
                    
                    @auth
                        <a href="{{ url('/checkout') }}" class="bg-black text-white px-8 py-3 rounded-xl font-bold uppercase tracking-widest hover:bg-gray-800 transition shadow-lg">
                            Finalizar Compra
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="bg-black text-white px-8 py-3 rounded-xl font-bold uppercase tracking-widest hover:bg-gray-800 transition shadow-lg">
                            Login para Comprar
                        </a>
                    @endauth
                </div>
            </div>
        @else
            <div class="text-center py-20 border-2 border-dashed border-gray-200 rounded-xl">
                <p class="text-gray-400 text-lg mb-4">Seu carrinho está vazio.</p>
                <a href="{{ route('home') }}" class="text-black font-bold border-b-2 border-black hover:text-gray-600 hover:border-gray-600 transition">
                    Ir às compras
                </a>
            </div>
        @endif
    </div>
</x-layout>