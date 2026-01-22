<x-layout>
    {{-- CABEÇALHO SIMPLES (Apenas Título) --}}
    <div class="container mx-auto px-4 pt-32 pb-8 text-left">
        <h1 class="text-2xl md:text-3xl font-black text-black uppercase tracking-tighter mb-4">{{ $title }}</h1>
    </div>

    {{-- LISTA DE PRODUTOS --}}
    <section class="container mx-auto px-4 pb-24">
        @if($products->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach($products as $product)
                    <div class="block group relative"
                         x-data="{ 
                             currentImage: '{{ Storage::url($product->image_url) }}', 
                             originalImage: '{{ Storage::url($product->image_url) }}',
                             hovering: false 
                         }"
                         @mouseenter="hovering = true"
                         @mouseleave="hovering = false">
                        
                        <a href="{{ route('shop.product', $product->slug) }}" class="block cursor-pointer">
                            
                            {{-- ÁREA DA IMAGEM --}}
                            <div class="relative overflow-hidden rounded-lg aspect-[3/4] mb-4 bg-white flex items-center justify-center">
                                
                                @if($product->created_at->diffInDays(now()) < 30)
                                    <div class="absolute top-3 left-3 bg-black text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest z-10 shadow-sm">Novo</div>
                                @endif

                                <img :src="currentImage" 
                                     class="object-contain w-full h-full transition duration-500 p-2"
                                     :class="hovering ? 'scale-105' : ''">

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
                                                {{-- [CORREÇÃO]: Imagem da variante inteira dentro do ícone --}}
                                                <img src="{{ Storage::url($variant->image) }}" class="w-full h-full object-contain p-0.5">
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            
                            <div class="text-center space-y-1">
                                @if($product->category) <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $product->category->name }}</p> @endif
                                <h4 class="font-bold text-gray-900">{{ $product->name }}</h4>

                                <div class="mt-1">
                                    @if($product->isOnSale())
                                        <div class="flex flex-col items-center justify-center gap-0.5">
                                            <span class="font-bold text-red-600 text-lg leading-tight">R$ {{ number_format($product->sale_price, 2, ',', '.') }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-gray-400 line-through">R$ {{ number_format($product->base_price, 2, ',', '.') }}</span>
                                                <span class="bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 rounded">-{{ $product->discount_percentage }}%</span>
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
                                <button type="submit" class="bg-black text-white border border-black px-8 py-2 rounded-xl uppercase font-bold text-xs tracking-widest shadow-md opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 hover:bg-white hover:text-black transition-all duration-300">
                                    Adicionar ao Carrinho
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-20 bg-gray-50 rounded-lg">
                <p class="text-gray-500 text-xl font-light">Nenhum produto encontrado.</p>
                <a href="{{ route('home') }}" class="inline-block mt-6 border-b-2 border-black text-black font-bold uppercase tracking-wide hover:text-gray-600 hover:border-gray-600 transition">
                    Voltar para a loja
                </a>
            </div>
        @endif
    </section>
</x-layout>