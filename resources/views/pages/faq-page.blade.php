<x-layout>
    {{-- Define o título da página no slot de head --}}
    @section('title', 'Dúvidas Gerais')

    {{-- Componente Principal com Alpine.js para Lógica de ScrollSpy e Navegação Suave --}}
    <div x-data="{ 
            activeSection: '', 
            
            {{-- Inicialização: Define o primeiro tópico como ativo e executa verificação de scroll --}}
            init() {
                if (!this.activeSection && '{{ count($faqTopics) > 0 }}') {
                    this.activeSection = '{{ $faqTopics[0]['slug'] }}';
                }
                this.onScroll(); 
            },

            {{-- Lógica de ScrollSpy: Monitora a posição do scroll para atualizar o menu lateral --}}
            onScroll() {
                // Offset de 250px para detectar a seção antes que ela atinja o topo exato
                const scrollPosition = window.pageYOffset + 250;
                const sections = document.querySelectorAll('section[id]');
                
                sections.forEach(section => {
                    const top = section.offsetTop;
                    const height = section.offsetHeight;
                    const id = section.getAttribute('id');

                    // Verifica se a janela de visualização está dentro dos limites da seção atual
                    if (scrollPosition >= top && scrollPosition < (top + height)) {
                        this.activeSection = id;
                    }
                });
            },

            {{-- Navegação Suave (Smooth Scroll): Com compensação de altura do Header fixo --}}
            scrollTo(id) {
                this.activeSection = id;
                const element = document.getElementById(id);
                if (element) {
                    const headerOffset = 180; 
                    const elementPosition = element.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
      
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            }
         }" 
         @scroll.window.throttle.10ms="onScroll()" 
         class="container mx-auto px-4 pt-48 pb-24">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-16 max-w-7xl mx-auto">
            
            {{-- 1. MENU LATERAL (Sticky): Segue o usuário durante a rolagem --}}
            <aside class="md:col-span-4 hidden md:block">
                <div class="sticky top-40 space-y-2">
                    <nav class="flex flex-col space-y-4">
                        @foreach($faqTopics as $topic)
                            <button 
                                @click.prevent="scrollTo('{{ $topic['slug'] }}')"
                                class="text-xl text-left w-full transition-all duration-300 pl-4 border-l-4"
                                :class="activeSection === '{{ $topic['slug'] }}' 
                                    ? 'border-black font-black text-black scale-105 origin-left' 
                                    : 'border-transparent font-medium text-gray-400 hover:text-gray-900 hover:border-gray-200'">
                                {{ $topic['title'] }}
                            </button>
                        @endforeach
                    </nav>
                </div>
            </aside>

            {{-- 2. CONTEÚDO PRINCIPAL: Renderização das seções e perguntas --}}
            <div class="md:col-span-8">
                
                <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight mb-16">
                    Dúvidas Frequentes
                </h1>

                {{-- Listagem de Tópicos (Categorias) --}}
                <div class="space-y-32"> 
                    @foreach($faqTopics as $topic)
                        
                        {{-- Seção de Categoria: Identificada pelo slug para o ScrollSpy --}}
                        <section id="{{ $topic['slug'] }}" class="relative">
                            
                            <h2 class="text-2xl font-bold text-gray-900 mb-8 border-b border-gray-100 pb-4 flex items-center">
                                {{-- Indicador Visual: Barra preta que aparece quando a seção está ativa --}}
                                <span class="w-2 h-8 bg-black mr-4 rounded-full" 
                                      x-show="activeSection === '{{ $topic['slug'] }}'" 
                                      x-transition.opacity.duration.500ms></span>
                                {{ $topic['title'] }}
                            </h2>

                            {{-- Listagem de Perguntas (Accordions) --}}
                            <div class="space-y-4">
                                @foreach($topic['questions'] as $item)
                                    <div x-data="{ open: false }" class="border border-gray-100 rounded-lg bg-white overflow-hidden group hover:border-gray-300 transition-colors">
                                        
                                        {{-- Botão Toggle da Pergunta --}}
                                        <button @click="open = !open" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                                            <span class="font-bold text-gray-800 text-lg group-hover:text-black">{{ $item['question'] }}</span>
                                            <span class="transform transition-transform duration-200 text-gray-400 group-hover:text-black" :class="open ? 'rotate-180' : ''">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </span>
                                        </button>

                                        {{-- Resposta: Expandível via Alpine x-collapse --}}
                                        <div x-show="open" x-collapse class="bg-white">
                                            <div class="p-6 pt-0 text-gray-600 leading-relaxed">
                                                {!! $item['answer'] !!}
                                            </div>
                                        </div>

                                    </div>
                                @endforeach
                            </div>

                        </section>
                    @endforeach
                    
                    {{-- Espaçador Final: Garante que o último tópico possa ser ativado no scroll --}}
                    <div class="h-64"></div>
                </div>

            </div>

        </div>
    </div>
</x-layout>