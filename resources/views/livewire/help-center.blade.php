{{-- resources/views/livewire/help-center.blade.php --}}

<div x-data="{ 
        activeSection: 'duvidas',
        scrollTo(id) {
            this.activeSection = id;
            const element = document.getElementById(id);
            if (element) {
                const headerOffset = 180; 
                const elementPosition = element.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
            }
        }
     }" 
     class="container mx-auto px-4 pt-48 pb-24 bg-white">
    
    {{-- Contêiner centralizado limitando a largura máxima da página --}}
    <div class="max-w-4xl mx-auto">
        
        {{-- SEÇÃO DE DÚVIDAS --}}
        <section id="duvidas">
            {{-- Título centralizado --}}
            <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight mb-12 text-center">
                Dúvidas Frequentes
            </h1>

            {{-- Buscador --}}
            <div class="mb-12">
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Buscar em nossas dúvidas..." 
                       class="w-full bg-white border border-gray-200 p-6 text-lg rounded-none focus:outline-none focus:ring-0 focus:border-black transition-colors hover:border-black">
            </div>

            {{-- Listagem de Categorias e FAQs --}}
            <div class="space-y-12">
                @forelse($categories as $category)
                    <div class="mb-16"> 
                        <h2 class="text-xl font-black uppercase text-gray-900 mb-8 border-b border-gray-100 pb-4">
                            {{ $category->name }}
                        </h2>
                        
                        <div class="space-y-4">
                            @foreach($category->faqs as $faq)
                                <div x-data="{ open: false }" class="border border-gray-200 bg-white overflow-hidden group hover:border-black transition-colors">
                                    <button @click="open = !open" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                                        <span class="font-bold text-gray-800 text-lg group-hover:text-black">{{ $faq->question }}</span>
                                        <span class="transform transition-transform duration-200 text-gray-400 group-hover:text-black" :class="open ? 'rotate-180' : ''">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </span>
                                    </button>
                                    <div x-show="open" x-collapse class="border-t border-gray-100" x-cloak>
                                        <div class="p-6 pt-4 text-gray-600 leading-relaxed">
                                            {!! $faq->answer !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-10">Nenhuma dúvida encontrada.</p>
                @endforelse
            </div>
        </section>

        {{-- CTA DE REDIRECIONAMENTO --}}
        <section class="mt-24 pt-16  border-gray-200 flex flex-col items-center justify-center text-center w-full">
            <h3 class="text-3xl sm:text-4xl font-black text-gray-900 uppercase tracking-tight mb-4">
                Não encontrou o que queria?
            </h3>
            <p class="text-lg sm:text-xl text-gray-500 mb-10">
                Nossa equipe de suporte está pronta para ajudar você.
            </p>
            
            <a href="{{ route('ticket.create', ['order' => $order_id]) }}" class="w-full block text-center bg-black text-white border-2 border-black px-10 py-6 font-black uppercase tracking-widest text-base sm:text-lg hover:bg-white hover:text-black transition-colors duration-300">
                Abra um Ticket
            </a>
        </section>

    </div>
</div>  