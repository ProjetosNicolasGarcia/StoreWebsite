@php
    // Query de Alta Performance (Executa APENAS 1 vez por página e não dentro de cada loop)
    $userFavoriteIds = auth()->check() ? auth()->user()->favorites()->pluck('product_id')->toArray() : [];
@endphp

<x-layout>
    {{-- PADDING DRASTICAMENTE REDUZIDO PARA pt-8 (O layout.blade.php já cuida de afastar do header fixo) --}}
    <div class="container mx-auto px-4 pt-8 pb-8 text-left">
        <h1 id="listing-title" class="text-2xl md:text-3xl font-black text-black uppercase tracking-tighter mb-4">
            {{ $title }}
        </h1>
    </div>

    <section class="container mx-auto px-4 pb-24" aria-labelledby="listing-title">
        @if($products->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach($products as $product)
                    <div class="block group relative"
                         x-data="{ 
                              currentImage: '{{ Storage::url($product->image_url) }}', 
                              originalImage: '{{ Storage::url($product->image_url) }}',
                              hovering: false,
                              isFavorite: {{ in_array($product->id, $userFavoriteIds ?? []) ? 'true' : 'false' }},
                              async toggleFav() {
                                  // 1. Invoca a sidebar de Login caso seja Visitante
                                  if (!{{ auth()->check() ? 'true' : 'false' }}) {
                                      window.dispatchEvent(new CustomEvent('open-auth-slider')); 
                                      return;
                                  }
                                  
                                  // 2. Optimistic UI: Inverte a cor do coração imediatamente
                                  let previous = this.isFavorite;
                                  this.isFavorite = !this.isFavorite;
                                  
                                  try {
                                      const token = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '{{ csrf_token() }}';
                                      const res = await fetch('/favoritos/toggle', {
                                          method: 'POST',
                                          headers: { 
                                              'Content-Type': 'application/json', 
                                              'X-CSRF-TOKEN': token, 
                                              'Accept': 'application/json' 
                                          },
                                          body: JSON.stringify({ product_id: {{ $product->id }} })
                                      });
                                      
                                      if (!res.ok) throw new Error();
                                      
                                      const data = await res.json();
                                      
                                      if(data.success) {
                                          window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.message } }));
                                      } else { 
                                          throw new Error(); 
                                      }
                                  } catch(e) {
                                      // Reverte a cor caso caia a internet ou o servidor falhe
                                      this.isFavorite = previous;
                                      window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Erro ao salvar favorito.', type: 'error' } }));
                                  }
                              }
                          }"
                         @mouseenter="hovering = true"
                         @mouseleave="hovering = false">
                        
                        {{-- CAIXA DA IMAGEM: Borda clara (border-gray-200) forçando o quadrado (aspect-ratio: 1/1) --}}
                        <div class="relative w-full border border-gray-200 mb-4 bg-white overflow-hidden" style="aspect-ratio: 1 / 1;">
                            
                            {{-- BOTÃO DE FAVORITAR OTIMIZADO --}}
                            <button type="button" 
                                    class="absolute z-30 transition-colors duration-300 focus:outline-none bg-transparent border-none p-0 m-0 cursor-pointer pointer-events-auto"
                                    :class="isFavorite ? 'text-red-600 opacity-100' : 'text-gray-400 opacity-0 group-hover:opacity-100 hover:text-red-500'"
                                    style="top: 0.75rem; right: 0.75rem;"
                                    aria-label="Favoritar {{ $product->name }}"
                                    :aria-pressed="isFavorite.toString()"
                                    @click.stop.prevent="toggleFav()">
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 hover:scale-110 transition-transform">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </button>

                            <a href="{{ route('shop.product', $product->slug) }}" class="absolute inset-0 block z-0 cursor-pointer" aria-label="Ver detalhes de {{ $product->name }}">
                                {{-- ETIQUETA "NOVO" (Dinâmica para a listagem) --}}
                                @if($product->created_at->diffInDays(now()) < 30)
                                    <div class="absolute top-3 left-3 bg-black text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest z-10 shadow-sm pointer-events-none" aria-hidden="true">
                                        Novo
                                    </div>
                                @endif
                                
                                <img src="{{ Storage::url($product->image_url) }}" 
                                     :src="currentImage" 
                                     class="absolute inset-0 w-full h-full object-contain p-6 transition-transform duration-500" 
                                     :class="hovering ? 'scale-105' : ''" 
                                     loading="lazy" 
                                     alt="{{ $product->name }}">
                            </a>

                            {{-- BOTÃO DE COMPRAR --}}
                            <div class="absolute bottom-0 left-0 w-full opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 z-20">
                                @php $variantCount = $product->variants->count(); @endphp

                                @if($variantCount > 1)
                                    <a href="{{ route('shop.product', $product->slug) }}" 
                                       class="w-full block bg-black text-white border-t border-gray-200 py-3 text-center uppercase font-bold text-xs tracking-widest hover:bg-white hover:text-black transition-colors duration-300 cursor-pointer"
                                       aria-label="Ver opções de {{ $product->name }} para adicionar ao carrinho">
                                        ADICIONAR AO CARRINHO
                                    </a>
                                @elseif($variantCount === 1)
                                    <form x-data="{ loading: false }" class="m-0 p-0"
                                          @submit.prevent="
                                              window.dispatchEvent(new CustomEvent('open-cart'));
                                              window.dispatchEvent(new CustomEvent('start-cart-loading'));
                                              loading = true;
                                              fetch('{{ route('cart.add', $product->id) }}', {
                                                  method: 'POST',
                                                  body: new FormData($event.target),
                                                  headers: {
                                                      'X-Requested-With': 'XMLHttpRequest',
                                                      'Accept': 'application/json'
                                                  }
                                              })
                                              .then(response => response.json())
                                              .then(data => {
                                                  loading = false;
                                                  if(data.success) {
                                                      Livewire.dispatch('cartUpdated');
                                                  } else {
                                                      window.dispatchEvent(new CustomEvent('update-cart-count'));
                                                      cartOpen = false;
                                                      alert(data.error || 'Erro ao adicionar ao carrinho');
                                                  }
                                              })
                                              .catch(error => {
                                                  loading = false;
                                                  window.dispatchEvent(new CustomEvent('update-cart-count'));
                                                  cartOpen = false;
                                                  alert('Ocorreu um erro de conexão.');
                                              });
                                          ">
                                        @csrf
                                        <input type="hidden" name="variant_id" value="{{ $product->variants->first()->id }}">
                                        <button type="submit" 
                                                :disabled="loading"
                                                class="w-full bg-black text-white border-t border-gray-200 py-3 uppercase font-bold text-xs tracking-widest hover:bg-white hover:text-black transition-colors duration-300 flex items-center justify-center cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed"
                                                aria-label="Adicionar {{ $product->name }} ao carrinho">
                                            <span x-show="!loading">Adicionar ao Carrinho</span>
                                            <span x-show="loading" class="flex items-center gap-2" style="display: none;" aria-hidden="true">
                                                <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </span>
                                        </button>
                                    </form>
                                @else
                                    <div class="w-full bg-gray-100 border-t border-gray-200 text-gray-400 py-3 uppercase font-bold text-xs tracking-widest flex items-center justify-center" aria-disabled="true">
                                        Indisponível
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        {{-- TEXTOS ALINHADOS À ESQUERDA --}}
                        <div class="text-left space-y-1">
                            @if($product->categories && $product->categories->isNotEmpty())
                                <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $product->categories->first()->name }}</p>
                            @endif
                            
                            <a href="{{ route('shop.product', $product->slug) }}" class="block cursor-pointer" aria-hidden="true" tabindex="-1">
                                <h4 class="font-bold text-gray-900 line-clamp-1">{{ $product->name }}</h4>
                            </a>

                            <div class="mt-1 cursor-text">
                                @if($product->isOnSale())
                                    <div class="flex flex-col items-start justify-center gap-0.5">
                                        <span class="font-bold text-red-600 text-lg leading-tight">
                                            R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-400 line-through" aria-label="Preço original">
                                                R$ {{ number_format($product->base_price, 2, ',', '.') }}
                                            </span>
                                            <span class="bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 rounded" aria-label="Desconto de">
                                                -{{ $product->discount_percentage }}%
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-gray-900 font-medium">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                                @endif
                            </div>

                            {{-- VARIANTES (MINIATURAS) --}}
                            @if($product->variants->whereNotNull('image')->count() > 0)
                                <div class="flex justify-start gap-2 pt-2 flex-wrap" aria-label="Variantes de produto">
                                    @foreach($product->visual_variants as $variant)
                                        <div @mouseenter="currentImage = '{{ Storage::url($variant->image) }}'"
                                             @mouseleave="currentImage = originalImage"
                                             @click.stop.prevent="window.location.href = '{{ route('shop.product', $product->slug) }}?variant={{ $variant->id }}'"
                                             @keydown.enter="window.location.href = '{{ route('shop.product', $product->slug) }}?variant={{ $variant->id }}'"
                                             role="link" 
                                             tabindex="0"
                                             aria-label="Visualizar variante do produto"
                                             class="w-8 h-8 rounded-none border border-gray-300 shadow-sm overflow-hidden cursor-pointer bg-white hover:border-black transition-all flex items-center justify-center">
                                            <img src="{{ Storage::url($variant->image) }}" 
                                                 class="w-full h-full object-contain p-0.5 pointer-events-none" 
                                                 loading="lazy" 
                                                 alt="Variante">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-20 rounded-lg" role="status" aria-live="polite">
                <p class="text-gray-500 text-xl font-light">Nenhum produto encontrado.</p>
                <a href="{{ route('home') }}" class="inline-block mt-6 border-b-2 border-black text-black font-bold uppercase tracking-wide hover:text-gray-600 hover:border-gray-600 transition cursor-pointer">
                    Voltar para a loja
                </a>
            </div>
        @endif
    </section>
</x-layout>