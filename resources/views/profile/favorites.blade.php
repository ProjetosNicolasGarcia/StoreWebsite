@php
    $userFavoriteIds = auth()->check() ? auth()->user()->favorites()->pluck('product_id')->toArray() : [];
@endphp

<x-profile.layout title="Meus Favoritos">
    <div class="mb-6">
        <h2 id="favorites-title" class="text-2xl font-black uppercase tracking-tighter text-gray-900">Meus Favoritos</h2>
        <p class="text-sm text-gray-500 uppercase tracking-widest mt-1">Produtos que você salvou</p>
    </div>

    @if($products->count() > 0)
        <div class="grid grid-cols-2 md:grid-cols-3 gap-8" aria-labelledby="favorites-title" role="region">
            @foreach($products as $product)
                {{-- Adicionado x-show e transição para o card sumir suavemente --}}
                <div class="block group relative"
                     x-data="{ 
                          showCard: true,
                          currentImage: '{{ Storage::url($product->image_url) }}', 
                          originalImage: '{{ Storage::url($product->image_url) }}',
                          hovering: false,
                          isFavorite: true,
                          async toggleFav() {
                              if (!{{ auth()->check() ? 'true' : 'false' }}) return;
                              
                              // Remoção imediata do DOM (Não afeta tempo de load)
                              this.isFavorite = false;
                              this.showCard = false; 
                              
                              try {
                                  const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                                  const res = await fetch('{{ route('favorites.toggle') }}', {
                                      method: 'POST',
                                      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                                      body: JSON.stringify({ product_id: {{ $product->id }} })
                                  });
                                  
                                  if (!res.ok) throw new Error();
                                  const data = await res.json();
                                  
                                  if(data.success) {
                                      window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message } }));
                                  } else { throw new Error(); }
                              } catch(e) {
                                  // Reaparece o card caso dê erro na exclusão
                                  this.isFavorite = true;
                                  this.showCard = true; 
                                  window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Erro ao remover dos favoritos.', type: 'error' } }));
                              }
                          }
                      }"
                     x-show="showCard"
                     x-transition.opacity.duration.300ms
                     @mouseenter="hovering = true"
                     @mouseleave="hovering = false">
                    
                    {{-- CAIXA DA IMAGEM --}}
                    <div class="relative w-full border border-gray-200 mb-4 bg-white overflow-hidden" style="aspect-ratio: 1 / 1;">
                        
                        {{-- BOTÃO DE REMOVER FAVORITO --}}
                        <button type="button" 
                                class="absolute z-30 transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-red-600 bg-transparent border-none p-0 m-0 cursor-pointer pointer-events-auto text-red-600 opacity-100"
                                style="top: 0.75rem; right: 0.75rem;"
                                aria-label="Remover {{ $product->name }} dos favoritos"
                                :aria-pressed="isFavorite.toString()"
                                @click.stop.prevent="toggleFav()">
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 hover:scale-110 transition-transform">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </button>

                        <a href="{{ route('shop.product', $product->slug) }}" class="absolute inset-0 block z-0 focus:outline-none focus:ring-2 focus:ring-black" aria-label="Ver detalhes de {{ $product->name }}">
                            <img src="{{ Storage::url($product->image_url) }}" :src="currentImage" class="absolute inset-0 w-full h-full object-contain p-6 transition-transform duration-500 cursor-pointer" :class="hovering ? 'scale-105' : ''" alt="{{ $product->name }}">
                        </a>
                    </div>
                    
                    {{-- TEXTOS E BLOCO DE PREÇO CORRIGIDO --}}
                    <div class="text-left space-y-1">
                        {{-- Escondido dos leitores e foco duplo, o link acima (na imagem) já atende a mesma função --}}
                        <a href="{{ route('shop.product', $product->slug) }}" class="block cursor-pointer" aria-hidden="true" tabindex="-1">
                            <h4 class="font-bold text-gray-900 line-clamp-1 hover:underline underline-offset-2">{{ $product->name }}</h4>
                        </a>
                        <div class="mt-1 cursor-text">
                            @if($product->isOnSale())
                                <div class="flex flex-col items-start justify-center gap-0.5">
                                    <span class="font-bold text-red-600 text-lg leading-tight" aria-label="Preço promocional">
                                        R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400 line-through" aria-label="Preço original">
                                            R$ {{ number_format($product->base_price, 2, ',', '.') }}
                                        </span>
                                        <span class="bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 rounded-none" aria-label="Desconto">
                                            -{{ $product->discount_percentage }}%
                                        </span>
                                    </div>
                                </div>
                            @else
                                <p class="text-gray-900 font-medium" aria-label="Preço">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-16 mt-8" role="status" aria-live="polite">
            <p class="text-gray-500 text-sm font-bold uppercase tracking-widest">Sua lista de desejos está vazia.</p>
            <a href="{{ route('home') }}" aria-label="Ir para a loja explorar produtos" class="inline-block mt-6 border border-black px-6 py-2 bg-black text-white text-xs font-bold uppercase tracking-widest hover:bg-white hover:text-black transition focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">Explorar Produtos</a>
        </div>
    @endif
</x-profile.layout>