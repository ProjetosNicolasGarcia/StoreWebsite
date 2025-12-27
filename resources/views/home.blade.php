<x-layout>
    @if($banners->count() > 0)
        <div class="relative w-full h-screen">
            <img src="{{ Storage::url($banners->first()->image_url) }}" alt="{{ $banners->first()->title }}" class="absolute inset-0 w-full h-full object-cover">
            
            <div class="absolute inset-0 bg-black/30"></div>

            <div class="absolute inset-0 flex items-center justify-center text-center">
                <div class="text-white max-w-2xl px-4">
                    <h2 class="text-5xl md:text-6xl font-bold mb-6 tracking-tight">{{ $banners->first()->title }}</h2>
                    @if($banners->first()->link_url)
                    <a href="{{ $banners->first()->link_url }}" class="inline-block bg-white text-gray-900 py-3 px-8 font-bold uppercase tracking-widest hover:bg-gray-100 transition">
                        Explorar Coleção
                    </a>
                    @endif
                </div>
            </div>

            <div class="absolute bottom-10 left-1/2 -translate-x-1/2 text-white animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
            </div>
        </div>
    @endif

    <section class="container mx-auto px-8 py-24">
        <div class="text-center mb-16">
            <h3 class="text-2xl md:text-3xl font-light tracking-widest uppercase">Destaques da Vitrine</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-12">
            @foreach($products as $product)
                <div class="group cursor-pointer transition duration-500 hover:scale-105">
                    <div class="relative aspect-[3/4] mb-6 overflow-hidden bg-gray-100">
                        <img src="{{ Storage::url($product->image_url) }}" alt="{{ $product->name }}" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 shadow-xl opacity-0 group-hover:opacity-100 transition duration-500 z-[-1]"></div>
                    </div>

                    <div class="text-center space-y-2">
                        <p class="text-sm text-gray-500 uppercase tracking-wider">{{ $product->category->name }}</p>
                        <h4 class="font-bold text-lg">{{ $product->name }}</h4>
                        <p class="text-xl font-light">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layout>