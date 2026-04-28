<x-profile.layout title="Meus Pedidos">
    {{-- Título da Seção --}}
    <h2 id="orders-heading" class="text-2xl font-bold mb-6 text-gray-900 border-b border-gray-100 pb-4">
        Meus Pedidos
    </h2>

    {{-- Verificação: Existe histórico de pedidos para este usuário? --}}
    @if($orders->count() > 0)
        {{-- Wrapper responsivo para tabelas longas em dispositivos móveis --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600" aria-labelledby="orders-heading">
                <thead class="bg-gray-50 uppercase text-xs font-bold text-gray-900">
                    <tr>
                        <th scope="col" class="px-6 py-4">Pedido #</th>
                        <th scope="col" class="px-6 py-4">Data</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($orders as $order)
                        <tr class="hover:bg-gray-50 transition">
                            {{-- ID do Pedido --}}
                            <td class="px-6 py-4 font-bold text-black" aria-label="Número do pedido {{ $order->id }}">
                                #{{ $order->id }}
                            </td>

                            {{-- Formatação de Data: Padrão Brasileiro (Dia/Mês/Ano) --}}
                            <td class="px-6 py-4">
                                {{ $order->created_at->format('d/m/Y') }}
                            </td>

                            {{-- Status do Pedido: Aplica cores diferentes conforme o estado --}}
                            <td class="px-6 py-4">
                                <span aria-label="Status do pedido: {{ ucfirst($order->status) }}" 
                                      class="px-3 py-1 rounded-full text-xs font-bold 
                                    {{ $order->status == 'paid' ? 'bg-green-100 text-green-800' : 
                                      ($order->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>

                            {{-- Valor Total: Formatado como moeda brasileira (Real) --}}
                            <td class="px-6 py-4 text-right font-bold text-gray-900" aria-label="Total pago: R$ {{ number_format($order->total ?? 0, 2, ',', '.') }}">
                                R$ {{ number_format($order->total ?? 0, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        {{-- Estado Vazio (Empty State): Exibido quando não há registros --}}
        <div class="text-center py-12" role="status" aria-live="polite">
            <div class="bg-gray-50 rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4" aria-hidden="true">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
            <p class="text-gray-500 text-lg">Você ainda não fez nenhum pedido.</p>
            
            {{-- Incentivo de Conversão: Link para a loja --}}
            <a href="{{ route('home') }}" aria-label="Ir para a página inicial da loja para começar a comprar" class="mt-4 inline-block text-black font-bold border-b-2 border-black hover:text-gray-600 hover:border-gray-600 transition focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                Começar a comprar
            </a>
        </div>
    @endif
</x-profile.layout>