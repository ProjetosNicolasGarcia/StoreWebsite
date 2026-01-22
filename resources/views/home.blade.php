<x-layout>
    {{-- 1. CARROSSEL PRINCIPAL (HERO) --}}
    @if($heroBanners->count() > 0)
        <div x-data="{ 
                activeSlide: 0, 
                slides: {{ $heroBanners->count() }},
                autoplay: null,
                next() { this.activeSlide = (this.activeSlide + 1) % this.slides },
                prev() { this.activeSlide = (this.activeSlide - 1 + this.slides) % this.slides },
                startAutoplay() { this.autoplay = setInterval(() => this.next(), 3000) },
                stopAutoplay() { clearInterval(this.autoplay) }
             }" 
             x-init="startAutoplay()"
             @mouseenter="stopAutoplay()"
             @mouseleave="startAutoplay()"
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
                        <img src="{{ Storage::url($banner->image_url) }}" class="absolute inset-0 w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/20"></div>

                        <div class="absolute bottom-0 left-0 p-8 md:p-16 w-full md:max-w-4xl pb-24 md:pb-16 flex flex-col justify-end">
                            <h2 class="text-4xl md:text-7xl font-bold text-white mb-4 uppercase drop-shadow-lg tracking-tighter leading-none">
                                {{ $banner->title }}
                            </h2>
                            @if($banner->description)
                                <p class="text-lg md:text-2xl text-white/90 font-medium drop-shadow-md max-w-2xl leading-relaxed">
                                    {{ $banner->description }}
                                </p>
                            @endif
                        </div>
                    </a>
                </div>
            @endforeach

            <button @click="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/30 backdrop-blur-sm text-white p-3 rounded-full transition hidden md:group-hover:block z-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </button>
            <button @click="next()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/30 backdrop-blur-sm text-white p-3 rounded-full transition hidden md:group-hover:block z-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </button>

            <div class="absolute bottom-6 left-0 right-0 flex justify-center space-x-3 z-20">
                @foreach($heroBanners as $index => $banner)
                    <button @click="activeSlide = {{ $index }}" 
                            :class="activeSlide === {{ $index }} ? 'bg-white scale-125' : 'bg-white/40 hover:bg-white/70'"
                            class="w-3 h-3 rounded-full transition-all duration-300 shadow-sm">
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 2. NOVIDADES (NEW ARRIVALS) --}}
    <section class="container mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h3 class="text-3xl font-bold uppercase tracking-widest">Novidades</h3>
            <p class="text-gray-500 mt-2">Os últimos lançamentos da loja</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            @foreach($newArrivals as $product)
                {{-- CARD PRODUTO COM INTERATIVIDADE ALPINE --}}
                <div class="block group relative"
                     x-data="{ 
                         currentImage: '{{ Storage::url($product->image_url) }}', 
                         originalImage: '{{ Storage::url($product->image_url) }}',
                         hovering: false 
                     }"
                     @mouseenter="hovering = true"
                     @mouseleave="hovering = false">
                    
                    <a href="{{ route('shop.product', $product->slug) }}" class="block cursor-pointer">
                        <div class="relative overflow-hidden rounded-lg aspect-[3/4] mb-4 bg-white flex items-center justify-center">
                            
                            <div class="absolute top-3 left-3 bg-black text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest z-10 shadow-sm">
                                Novo
                            </div>

                            {{-- Imagem Principal Reativa --}}
                            <img :src="currentImage" 
                                 class="object-contain w-full h-full transition duration-500 p-2"
                                 :class="hovering ? 'scale-105' : ''">

                            {{-- Miniaturas das Variantes (Aparecem no Hover) --}}
                            @if($product->variants->whereNotNull('image')->count() > 0)
                                <div x-show="hovering"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 translate-y-2"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     class="absolute bottom-3 left-0 right-0 flex justify-center gap-2 px-2 z-20 flex-wrap">
                                    
                                    @foreach($product->variants->whereNotNull('image')->unique('image')->take(4) as $variant)
                                        <div @mouseenter="currentImage = '{{ Storage::url($variant->image) }}'"
                                             @mouseleave="currentImage = originalImage"
                                             class="w-10 h-10 rounded-md border border-gray-200 shadow-sm overflow-hidden cursor-pointer bg-white hover:border-black transition-all transform hover:scale-110 flex items-center justify-center">
                                            {{-- [CORREÇÃO]: object-contain e p-0.5 para a imagem da variante não cortar --}}
                                            <img src="{{ Storage::url($variant->image) }}" class="w-full h-full object-contain p-0.5">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        
                        <div class="text-center space-y-1">
                            @if($product->category)
                                <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $product->category->name }}</p>
                            @endif
                            <h4 class="font-bold text-gray-900">{{ $product->name }}</h4>

                            <div class="mt-1">
                                @if($product->isOnSale())
                                    <div class="flex flex-col items-center justify-center gap-0.5">
                                        <span class="font-bold text-red-600 text-lg leading-tight">
                                            R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-400 line-through">
                                                R$ {{ number_format($product->base_price, 2, ',', '.') }}
                                            </span>
                                            <span class="bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 rounded">
                                                -{{ $product->discount_percentage }}%
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-gray-600">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                                @endif
                            </div>
                        </div>
                    </a>

                    <div class="pt-2 h-10 flex items-center justify-center">
                        <form action="{{ route('cart.add', $product->id) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                class="bg-black text-white border border-black px-8 py-2 rounded-xl uppercase font-bold text-xs tracking-widest shadow-md 
                                    opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 
                                    hover:bg-white hover:text-black transition-all duration-300">
                                Adicionar ao Carrinho
                            </button>
                        </form>
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
                    <img src="{{ Storage::url($collection->image_url) }}" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
                 @else
                    <div class="w-full h-full bg-gray-900 flex items-center justify-center text-white">Sem Imagem</div>
                 @endif
                 
                 <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/20"></div>

                 <div class="absolute bottom-0 left-0 p-6 md:p-12 w-full md:max-w-4xl flex flex-col justify-end items-start text-left z-10">
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
                        {{-- CARD PRODUTO COM INTERATIVIDADE ALPINE --}}
                        <div class="block group relative"
                             x-data="{ 
                                 currentImage: '{{ Storage::url($product->image_url) }}', 
                                 originalImage: '{{ Storage::url($product->image_url) }}',
                                 hovering: false 
                             }"
                             @mouseenter="hovering = true"
                             @mouseleave="hovering = false">
                            
                            <a href="{{ route('shop.product', $product->slug) }}" class="block cursor-pointer">
                                <div class="relative overflow-hidden rounded-lg aspect-[3/4] mb-4 bg-white flex items-center justify-center">
                                    
                                    {{-- Imagem Reativa --}}
                                    <img :src="currentImage" 
                                         class="object-contain w-full h-full transition duration-500 p-2"
                                         :class="hovering ? 'scale-105' : ''">

                                    {{-- Miniaturas das Variantes --}}
                                    @if($product->variants->whereNotNull('image')->count() > 0)
                                        <div x-show="hovering"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 translate-y-2"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             class="absolute bottom-3 left-0 right-0 flex justify-center gap-2 px-2 z-20 flex-wrap">
                                            
                                            @foreach($product->variants->whereNotNull('image')->unique('image')->take(4) as $variant)
                                                <div @mouseenter="currentImage = '{{ Storage::url($variant->image) }}'"
                                                     @mouseleave="currentImage = originalImage"
                                                     class="w-10 h-10 rounded-md border border-gray-200 shadow-sm overflow-hidden cursor-pointer bg-white hover:border-black transition-all transform hover:scale-110 flex items-center justify-center">
                                                    {{-- [CORREÇÃO]: object-contain e p-0.5 aqui também --}}
                                                    <img src="{{ Storage::url($variant->image) }}" class="w-full h-full object-contain p-0.5">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="text-center space-y-1">
                                    @if($product->category)
                                        <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $product->category->name }}</p>
                                    @endif
                                    <h4 class="font-bold text-gray-900">{{ $product->name }}</h4>
                                    
                                    <div class="mt-1">
                                        @if($product->isOnSale())
                                            <div class="flex flex-col items-center justify-center gap-0.5">
                                                <span class="font-bold text-red-600 text-lg leading-tight">
                                                    R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-400 line-through">
                                                        R$ {{ number_format($product->base_price, 2, ',', '.') }}
                                                    </span>
                                                    <span class="bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 rounded">
                                                        -{{ $product->discount_percentage }}%
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-gray-600">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </a>

                            <div class="pt-2 h-10 flex items-center justify-center">
                                <form action="{{ route('cart.add', $product->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                        class="bg-black text-white border border-black px-8 py-2 rounded-xl uppercase font-bold text-xs tracking-widest shadow-md 
                                            opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 
                                            hover:bg-white hover:text-black transition-all duration-300">
                                        Adicionar ao Carrinho
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-center">
                    <a href="{{ url('/collections/' . $collection->slug) }}" 
                       class="inline-block border border-black rounded-xl bg-white text-black px-12 py-3 uppercase font-bold text-sm tracking-widest hover:bg-black hover:text-white transition duration-300">
                        Ver mais
                    </a>
                </div>
            </div>
        </section>
    @endforeach
</x-layout>