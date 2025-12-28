<x-layout>
    {{-- CABEÇALHO SIMPLES (Apenas Título) --}}
    {{-- 'pt-32': Espaço extra no topo para o título não ficar atrás do menu --}}
    <div class="container mx-auto px-4 pt-32 pb-8 text-center">
        <h1 class="text-4xl md:text-6xl font-black text-gray-900 uppercase tracking-tighter mb-4">
            {{ $title }}
        </h1>
        
        {{-- Descrição removida conforme solicitado --}}
    </div>

    {{-- LISTA DE PRODUTOS --}}
    <section class="container mx-auto px-4 pb-24">
        @if($products->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach($products as $product)
                    <div class="group cursor-pointer">
                        {{-- Imagem --}}
                        <div class="relative overflow-hidden rounded-lg aspect-[3/4] mb-4 bg-gray-50 flex items-center justify-center">
                            
                            {{-- Tag de Novo --}}
                            @if($product->created_at->diffInDays(now()) < 30)
                                <div class="absolute top-3 left-3 bg-black text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest z-10 shadow-sm">
                                    Novo
                                </div>
                            @endif

                            <img src="{{ Storage::url($product->image_url) }}" 
                                 class="object-contain w-full h-full p-4 transition duration-500 group-hover:scale-110">
                        </div>
                        
                        {{-- Informações --}}
                        <div class="text-center space-y-1">
                            @if($product->category)
                                <p class="text-xs text-gray-500 uppercase tracking-widest">{{ $product->category->name }}</p>
                            @endif
                            <h4 class="font-bold text-gray-900">{{ $product->name }}</h4>
                            <p class="text-gray-600">R$ {{ number_format($product->base_price, 2, ',', '.') }}</p>
                            
                            {{-- Botão Carrinho --}}
                            <div class="pt-2 h-10 flex items-center justify-center">
                                <button class="bg-black text-white px-6 py-2 uppercase font-bold text-xs tracking-widest hover:bg-gray-800 shadow-md rounded 
                                               opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                                    Adicionar ao Carrinho
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Caso não tenha produtos --}}
            <div class="text-center py-20 bg-gray-50 rounded-lg">
                <p class="text-gray-500 text-xl font-light">Nenhum produto encontrado nesta seção.</p>
                <a href="{{ route('home') }}" class="inline-block mt-6 border-b-2 border-black text-black font-bold uppercase tracking-wide hover:text-gray-600 hover:border-gray-600 transition">
                    Voltar para a loja
                </a>
            </div>
        @endif
    </section>
</x-layout>