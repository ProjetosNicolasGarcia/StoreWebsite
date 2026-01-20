<x-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Seu Carrinho</h1>

        @if($items->count() > 0)
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="p-4">Produto</th>
                            <th class="p-4">Qtd</th>
                            <th class="p-4">Preço Unit.</th>
                            <th class="p-4">Subtotal</th> <th class="p-4">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        {{-- Segurança: Pula se a variante foi deletada --}}
                        @if(!$item->variant) @continue @endif

                        <tr class="border-b">
                            <td class="p-4 flex items-center gap-4">
                                {{-- Imagem da Variante (ou do Produto se não tiver variante) --}}
                                @php
                                    $image = $item->variant->image ?? ($item->variant->images[0] ?? $item->product->image_url);
                                @endphp
                                <img src="{{ asset('storage/' . $image) }}" class="w-16 h-16 object-cover rounded border border-gray-200">
                                
                                <div class="flex flex-col items-start">
                                    {{-- 1. Nome do Produto --}}
                                    <a href="{{ route('shop.product', $item->product->slug) }}" class="font-bold text-gray-900 hover:text-gray-600 transition">
                                        {{ $item->product->name }}
                                    </a>

                                    {{-- 2. Categoria (Exibida em cinza e maiúsculo) --}}
                                    @if($item->product->category)
                                        <span class="text-[10px] uppercase tracking-widest text-gray-400 mb-1">
                                            {{ $item->product->category->name }}
                                        </span>
                                    @endif
                                    
                                    {{-- 3. Atributos Selecionados (Exibidos abaixo da categoria) --}}
                                    @if($item->variant && $item->variant->options)
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($item->variant->options as $key => $value)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                    {{ ucfirst($key) }}: {{ $value }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Padrão</span>
                                    @endif
                                </div>
                            </td>
                            
                            <td class="p-4">
                                {{-- Controles de quantidade (simplificado) --}}
                                <div class="flex items-center gap-2">
                                    {{-- Botões de +/- aqui se desejar --}}
                                    {{ $item->quantity }}
                                </div>
                            </td>

                            {{-- 3. PREÇO: Pega da variante --}}
                            <td class="p-4">
                                R$ {{ number_format($item->variant->final_price, 2, ',', '.') }}
                            </td>

                            {{-- 4. SUBTOTAL: Qtd * Preço Variante --}}
                            <td class="p-4 font-bold">
                                R$ {{ number_format($item->variant->final_price * $item->quantity, 2, ',', '.') }}
                            </td>

                            <td class="p-4">
                                <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">Remover</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div class="p-6 bg-gray-50 flex justify-between items-center">
                    <span class="text-xl font-bold">Total: R$ {{ number_format($total, 2, ',', '.') }}</span>
                    
                    @auth
                        <a href="{{ url('/checkout') }}" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800">Finalizar Compra</a>
                    @else
                        <a href="{{ route('login') }}" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800">Fazer Login para Finalizar</a>
                    @endauth
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">Seu carrinho está vazio.</p>
                <a href="{{ route('home') }}" class="text-blue-600 hover:underline mt-2 inline-block">Continuar comprando</a>
            </div>
        @endif
    </div>
</x-layout>