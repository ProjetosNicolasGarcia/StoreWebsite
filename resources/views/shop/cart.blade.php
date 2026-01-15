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
                            <th class="p-4">Preço</th>
                            <th class="p-4">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr class="border-b">
                            <td class="p-4 flex items-center gap-4">
                                <img src="{{ asset('storage/' . $item->product->image_url) }}" class="w-16 h-16 object-cover rounded">
                                <span class="font-semibold">{{ $item->product->name }}</span>
                            </td>
                            <td class="p-4">{{ $item->quantity }}</td>
                            <td class="p-4">R$ {{ number_format($item->product->base_price * $item->quantity, 2, ',', '.') }}</td>
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
                    @auth
                        <span class="text-xl font-bold">Total: R$ {{ number_format($total, 2, ',', '.') }}</span>
                        <a href="{{ url('/checkout') }}" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800">Finalizar Compra</a>
                    @else
                        <a href="{{ route('login') }}" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-800">Fazer Login para Finalizar</a>
                        <p class="mt-2 text-center text-sm text-gray-500"> É necessário ter uma conta para continuar. </p>
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