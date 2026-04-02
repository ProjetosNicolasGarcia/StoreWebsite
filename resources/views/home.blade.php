@php
    // Query de Alta Performance (Executa APENAS 1 vez por página e não dentro de cada loop)
    $userFavoriteIds = auth()->check() ? auth()->user()->favorites()->pluck('product_id')->toArray() : [];
@endphp

<x-layout>
    {{-- 1. CARROSSEL PRINCIPAL (HERO) --}}
    @if($heroBanners->count() > 0)
        <div x-data="{ 
                activeSlide: 0, 
                slides: {{ $heroBanners->count() }},
                interval: 5000, {{-- Tempo de exibição de cada banner (5 segundos) --}}
                autoplay: null,
                next() { this.activeSlide = (this.activeSlide + 1) % this.slides },
                prev() { this.activeSlide = (this.activeSlide - 1 + this.slides) % this.slides },
                startAutoplay() { 
                    if (this.autoplay) clearInterval(this.autoplay);
                    this.autoplay = setInterval(() => this.next(), this.interval); 
                },
                goTo(index) {
                    this.activeSlide = index;
                    this.startAutoplay(); {{-- Reseta o timer ao clicar manualmente --}}
                }
             }" 
             x-init="startAutoplay()"
             class="relative w-full h-[65vh] md:h-screen group overflow-hidden bg-black">
            
            @foreach($heroBanners as $index => $banner)
                <div x-show="activeSlide === {{ $index }}"
                     x-transition:enter="transition transform duration-700 ease-in-out"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition transform duration-700 ease-in-out absolute inset-0"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="absolute inset-0 w-full h-full">
                    
                    <a href="{{ $banner->link_url ?? '#' }}" class="block w-full h-full relative">
                        <img src="{{ Storage::url($banner->image_url) }}" 
                             class="absolute inset-0 w-full h-full object-cover cursor-pointer"
                             fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}" 
                             loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                             alt="{{ $banner->title }}">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/20 pointer-events-none"></div>

                        <div class="absolute bottom-0 left-0 p-8 md:p-16 w-full md:max-w-4xl pb-24 md:pb-16 flex flex-col justify-end pointer-events-none">
                            <h2 class="text-4xl md:text-7xl font-bold text-white mb-4 uppercase drop-shadow-lg tracking-tighter leading-none pointer-events-auto cursor-text">
                                {{ $banner->title }}
                            </h2>
                            @if($banner->description)
                                <p class="text-lg md:text-2xl text-white/90 font-medium drop-shadow-md max-w-2xl leading-relaxed pointer-events-auto cursor-text">
                                    {{ $banner->description }}
                                </p>
                            @endif
                        </div>
                    </a>
                </div>
            @endforeach

            <button @click="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/30 backdrop-blur-sm text-white p-3 rounded-full transition hidden md:group-hover:block z-20 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </button>
            <button @click="next()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/30 backdrop-blur-sm text-white p-3 rounded-full transition hidden md:group-hover:block z-20 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </button>

            {{-- CORREÇÃO: PROGRESS BAR MAIS VISÍVEL --}}
            <div class="absolute bottom-6 md:bottom-10 left-0 w-full z-30 px-6 sm:px-12 flex items-center justify-center">
                <div class="flex gap-3 items-center w-full max-w-5xl">
                    @foreach($heroBanners as $index => $banner)
                        <button 
                            @click="goTo({{ $index }})"
                            type="button"
                            class="flex-1 h-1.5 sm:h-2 bg-white/40 backdrop-blur-sm rounded-full overflow-hidden focus:outline-none cursor-pointer relative group shadow-[0_2px_4px_rgba(0,0,0,0.5)] border border-white/10"
                            aria-label="Ir para banner {{ $index + 1 }}"
                        >
                            <div 
                                class="absolute top-0 left-0 h-full bg-white transition-all ease-linear shadow-[0_0_8px_rgba(255,255,255,0.8)]"
                                :style="activeSlide === {{ $index }} ? 'width: 100%; transition-duration: ' + interval + 'ms;' : 'width: 0%; transition-duration: 0ms;'"
                            ></div>
                            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-30 transition-opacity"></div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- 2. NOVIDADES (NEW ARRIVALS) --}}
    <section class="container mx-auto px-4 py-16">
        <div class="text-left mb-12">
            <h3 class="text-3xl font-black uppercase tracking-widest text-gray-900">Novidades</h3>
            <p class="text-gray-500 mt-2 uppercase tracking-widest text-xs font-bold">Os últimos lançamentos da loja</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            @foreach($newArrivals as $product)
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
                    
                    {{-- CAIXA DA IMAGEM --}}
                    <div class="relative w-full border border-gray-200 mb-4 bg-white overflow-hidden" style="aspect-ratio: 1 / 1;">
                        
                        {{-- BOTÃO DE FAVORITAR OTIMIZADO --}}
                        <button type="button" 
                                class="absolute z-30 transition-colors duration-300 focus:outline-none bg-transparent border-none p-0 m-0 cursor-pointer pointer-events-auto"
                                :class="isFavorite ? 'text-red-600 opacity-100' : 'text-gray-400 opacity-0 group-hover:opacity-100 hover:text-red-500'"
                                style="top: 0.75rem; right: 0.75rem;"
                                aria-label="Favoritos"
                                @click.stop.prevent="toggleFav()">
                            <svg xmlns="http://www.w3.org/2000/svg" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 hover:scale-110 transition-transform">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </button>

                        <a href="{{ route('shop.product', $product->slug) }}" class="absolute inset-0 block z-0">
                            <div class="absolute top-3 left-3 bg-black text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest z-10 shadow-sm pointer-events-none">
                                Novo
                            </div>
                            
                            <img src="{{ Storage::url($product->image_url) }}" 
                                 :src="currentImage" 
                                 class="absolute inset-0 w-full h-full object-contain p-6 transition-transform duration-500 cursor-pointer" 
                                 :class="hovering ? 'scale-105' : ''" 
                                 loading="lazy" 
                                 alt="{{ $product->name }}">
                        </a>

                        {{-- BOTÃO DE COMPRAR --}}
                        <div class="absolute bottom-0 left-0 w-full opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 z-20">
                            @php $variantCount = $product->variants->count(); @endphp

                            @if($variantCount > 1)
                                <a href="{{ route('shop.product', $product->slug) }}" 
                                   class="w-full block bg-black text-white border-t border-gray-200 py-3 text-center uppercase font-bold text-xs tracking-widest hover:bg-white hover:text-black transition-colors duration-300 cursor-pointer">
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
                                            class="w-full bg-black text-white border-t border-gray-200 py-3 uppercase font-bold text-xs tracking-widest hover:bg-white hover:text-black transition-colors duration-300 flex items-center justify-center cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                                        <span x-show="!loading">Adicionar ao Carrinho</span>
                                        <span x-show="loading" class="flex items-center gap-2" style="display: none;">
                                            <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </span>
                                    </button>
                                </form>
                            @else
                                <div class="w-full bg-gray-100 border-t border-gray-200 text-gray-400 py-3 uppercase font-bold text-xs tracking-widest flex items-center justify-center cursor-default">
                                    Indisponível
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- TEXTOS ALINHADOS À ESQUERDA --}}
                    <div class="text-left space-y-1">
                        @if($product->categories->first())
                            <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $product->categories->first()->name }}</p>
                        @endif
                        
                        <a href="{{ route('shop.product', $product->slug) }}" class="block cursor-pointer">
                            <h4 class="font-bold text-gray-900 line-clamp-1 hover:underline underline-offset-2">{{ $product->name }}</h4>
                        </a>

                        <div class="mt-1 cursor-text">
                            @if($product->isOnSale())
                                <div class="flex flex-col items-start justify-center gap-0.5">
                                    <span class="font-bold text-red-600 text-lg leading-tight">
                                        R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400 line-through">
                                            R$ {{ number_format($product->base_price, 2, ',', '.') }}
                                        </span>
                                        <span class="bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 rounded-none">
                                            -{{ $product->discount_percentage }}%
                                        </span>
                                    </div>
                                </div>
                            @else
                                <p class="text-gray-900 font-medium">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                            @endif
                        </div>

                        {{-- VARIANTES --}}
                        @if($product->variants->whereNotNull('image')->count() > 0)
                            <div class="flex justify-start gap-2 pt-2 flex-wrap">
                                @foreach($product->visual_variants as $variant)
                                    <div @mouseenter="currentImage = '{{ Storage::url($variant->image) }}'"
                                         @mouseleave="currentImage = originalImage"
                                         @click.stop.prevent="window.location.href = '{{ route('shop.product', $product->slug) }}?variant={{ $variant->id }}'"
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
    </section>

    {{-- 3. COLEÇÕES --}}
    @foreach($collections as $collection)
        <section class="mb-24">
            <div class="relative w-full h-[300px] md:h-[950px] mb-12 group overflow-hidden">
                 @if($collection->image_url)
                    <img src="{{ Storage::url($collection->image_url) }}" 
                         loading="lazy" 
                         class="w-full h-full object-cover transition duration-700 group-hover:scale-105 pointer-events-none" 
                         alt="{{ $collection->title }}">
                 @else
                    <div class="w-full h-full bg-gray-900 flex items-center justify-center text-white pointer-events-none">Sem Imagem</div>
                 @endif
                 
                 <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/20 pointer-events-none"></div>

                 <div class="absolute bottom-0 left-0 p-6 md:p-12 w-full md:max-w-4xl flex flex-col justify-end items-start text-left z-10 pointer-events-none">
                    <h2 class="text-3xl md:text-5xl font-black uppercase mb-2 tracking-tighter drop-shadow-md text-white">
                        {{ $collection->title }}
                    </h2>
                    @if($collection->description)
                        <p class="text-sm md:text-xl text-white/90 font-medium drop-shadow-sm max-w-2xl">
                            {{ $collection->description }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="container mx-auto px-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
                    @foreach($collection->products as $product)
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
                            
                            {{-- CAIXA DA IMAGEM --}}
                            <div class="relative w-full border border-gray-200 mb-4 bg-white overflow-hidden" style="aspect-ratio: 1 / 1;">
                                
                                {{-- BOTÃO DE FAVORITAR OTIMIZADO --}}
                                <button type="button" 
                                        class="absolute z-30 transition-colors duration-300 focus:outline-none bg-transparent border-none p-0 m-0 cursor-pointer pointer-events-auto"
                                        :class="isFavorite ? 'text-red-600 opacity-100' : 'text-gray-400 opacity-0 group-hover:opacity-100 hover:text-red-500'"
                                        style="top: 0.75rem; right: 0.75rem;"
                                        aria-label="Favoritos"
                                        @click.stop.prevent="toggleFav()">
                                    <svg xmlns="http://www.w3.org/2000/svg" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 hover:scale-110 transition-transform">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                    </svg>
                                </button>

                                <a href="{{ route('shop.product', $product->slug) }}" class="absolute inset-0 block z-0">
                                    <img src="{{ Storage::url($product->image_url) }}" 
                                         :src="currentImage" 
                                         class="absolute inset-0 w-full h-full object-contain p-6 transition-transform duration-500 cursor-pointer"
                                         :class="hovering ? 'scale-105' : ''" 
                                         loading="lazy" 
                                         alt="{{ $product->name }}">
                                </a>

                                {{-- BOTÃO DE COMPRAR --}}
                                <div class="absolute bottom-0 left-0 w-full opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 z-20">
                                    @php $variantCount = $product->variants->count(); @endphp

                                    @if($variantCount > 1)
                                        <a href="{{ route('shop.product', $product->slug) }}" 
                                           class="w-full block bg-black text-white border-t border-gray-200 py-3 text-center uppercase font-bold text-xs tracking-widest hover:bg-white hover:text-black transition-colors duration-300 cursor-pointer">
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
                                                    class="w-full bg-black text-white border-t border-gray-200 py-3 uppercase font-bold text-xs tracking-widest hover:bg-white hover:text-black transition-colors duration-300 flex items-center justify-center cursor-pointer disabled:opacity-70 disabled:cursor-not-allowed">
                                                <span x-show="!loading">Adicionar ao Carrinho</span>
                                                <span x-show="loading" class="flex items-center gap-2" style="display: none;">
                                                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                </span>
                                            </button>
                                        </form>
                                    @else
                                        <div class="w-full bg-gray-100 border-t border-gray-200 text-gray-400 py-3 uppercase font-bold text-xs tracking-widest flex items-center justify-center cursor-default">
                                            Indisponível
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- TEXTOS ALINHADOS À ESQUERDA --}}
                            <div class="text-left space-y-1">
                                @if($product->categories->first())
                                    <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $product->categories->first()->name }}</p>
                                @endif
                                
                                <a href="{{ route('shop.product', $product->slug) }}" class="block cursor-pointer">
                                    <h4 class="font-bold text-gray-900 line-clamp-1 hover:underline underline-offset-2">{{ $product->name }}</h4>
                                </a>

                                <div class="mt-1 cursor-text">
                                    @if($product->isOnSale())
                                        <div class="flex flex-col items-start justify-center gap-0.5">
                                            <span class="font-bold text-red-600 text-lg leading-tight">
                                                R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-400 line-through">
                                                    R$ {{ number_format($product->base_price, 2, ',', '.') }}
                                                </span>
                                                <span class="bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 rounded-none">
                                                    -{{ $product->discount_percentage }}%
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-gray-900 font-medium">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                                    @endif
                                </div>

                                {{-- VARIANTES --}}
                                @if($product->variants->whereNotNull('image')->count() > 0)
                                    <div class="flex justify-start gap-2 pt-2 flex-wrap">
                                        @foreach($product->visual_variants as $variant)
                                            <div @mouseenter="currentImage = '{{ Storage::url($variant->image) }}'"
                                                 @mouseleave="currentImage = originalImage"
                                                 @click.stop.prevent="window.location.href = '{{ route('shop.product', $product->slug) }}?variant={{ $variant->id }}'"
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

                <div class="flex justify-center">
                    <a href="{{ url('/collections/' . $collection->slug) }}" 
                       class="inline-block border border-black rounded-none bg-white text-black px-12 py-3 uppercase font-bold text-sm tracking-widest hover:bg-black hover:text-white transition duration-300 cursor-pointer">
                        Ver mais
                    </a>
                </div>
            </div>
        </section>
    @endforeach
</x-layout>