<x-layout>
    @if($banners->count() > 0)
        @php
            $banner = $banners->first();
        @endphp
        
        {{-- Banner Principal --}}
        <a href="{{ $banner->link_url ?? '#' }}" class="relative w-full h-[65vh] md:h-screen block group overflow-hidden">
            
            {{-- Imagem do Banner --}}
            <img src="{{ Storage::url($banner->image_url) }}" 
                 alt="{{ $banner->title }}" 
                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
            
            {{-- Gradiente --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-80"></div>

            {{-- Conteúdo --}}
            <div class="absolute bottom-0 left-0 p-6 md:p-16 w-full md:max-w-4xl flex flex-col justify-end h-full">
                <h2 class="text-4xl md:text-7xl font-bold text-white mb-2 tracking-tighter uppercase leading-none drop-shadow-md">
                    {{ $banner->title }}
                </h2>
                
                @if(isset($banner->description) && $banner->description)
                    <p class="text-gray-200 text-lg md:text-2xl font-medium tracking-wide drop-shadow-sm max-w-2xl mt-2">
                        {{ $banner->description }}
                    </p>
                @endif
            </div>
        </a>
    @endif

    <section class="container mx-auto px-4 md:px-8 py-16 md:py-24">
        <div class="text-center mb-12 md:mb-16">
            <h3 class="text-2xl md:text-3xl font-light tracking-widest uppercase">Destaques da Vitrine</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-12">
            @foreach($products as $product)
                {{-- Card do Produto --}}
                <div class="group cursor-pointer transition duration-500 hover:scale-105">
                    
                    {{-- Imagem com ajuste para não cortar (object-contain) --}}
                    {{-- bg-white: Fundo branco para preencher espaços vazios --}}
                    <div class="relative aspect-[3/4] mb-6 overflow-hidden bg-white rounded-md shadow-sm">
                        <img src="{{ Storage::url($product->image_url) }}" 
                             alt="{{ $product->name }}" 
                             class="w-full h-full object-contain p-2 transition duration-700 group-hover:scale-110">
                        
                        {{-- Sombra hover --}}
                        <div class="absolute inset-0 shadow-xl opacity-0 group-hover:opacity-100 transition duration-500 z-[-1]"></div>
                    </div>

                    {{-- Informações Centralizadas --}}
                    <div class="text-center space-y-2">
                        <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $product->category->name }}</p>
                        <h4 class="font-bold text-lg text-gray-900 leading-tight">{{ $product->name }}</h4>
                        <p class="text-lg font-light text-gray-700">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layout>