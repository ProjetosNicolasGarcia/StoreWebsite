<x-layout>
    @section('title', 'Dúvidas Gerais')

    <div x-data="{ 
            activeSection: '', 
            
            init() {
                // Marca o primeiro tópico como ativo ao carregar, se nenhum estiver
                if (!this.activeSection && '{{ count($faqTopics) > 0 }}') {
                    this.activeSection = '{{ $faqTopics[0]['slug'] }}';
                }
                this.onScroll(); 
            },

            onScroll() {
                // Pega a posição atual do scroll + um espaço para compensar o topo (Header)
                // 200px é um bom valor para o menu mudar um pouco antes do título chegar no topo
                const scrollPosition = window.pageYOffset + 250;

                const sections = document.querySelectorAll('section[id]');
                
                sections.forEach(section => {
                    const top = section.offsetTop;
                    const height = section.offsetHeight;
                    const id = section.getAttribute('id');

                    // LÓGICA CORRIGIDA:
                    // Verifica se o scroll está ENTRE o início e o fim desta seção
                    if (scrollPosition >= top && scrollPosition < (top + height)) {
                        this.activeSection = id;
                    }
                });
            },

            scrollTo(id) {
                this.activeSection = id;
                const element = document.getElementById(id);
                if (element) {
                    // Offset manual para garantir que o título não fique escondido atrás do header
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
            
            {{-- 1. MENU LATERAL --}}
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

            {{-- 2. CONTEÚDO PRINCIPAL --}}
            <div class="md:col-span-8">
                
                <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight mb-16">
                    Dúvidas Frequentes
                </h1>

                <div class="space-y-32"> {{-- Espaço grande entre tópicos para facilitar a detecção --}}
                    @foreach($faqTopics as $topic)
                        
                        {{-- Seção do Tópico --}}
                        <section id="{{ $topic['slug'] }}" class="relative">
                            
                            <h2 class="text-2xl font-bold text-gray-900 mb-8 border-b border-gray-100 pb-4 flex items-center">
                                <span class="w-2 h-8 bg-black mr-4 rounded-full" 
                                      x-show="activeSection === '{{ $topic['slug'] }}'" 
                                      x-transition.opacity.duration.500ms></span>
                                {{ $topic['title'] }}
                            </h2>

                            <div class="space-y-4">
                                @foreach($topic['questions'] as $item)
                                    <div x-data="{ open: false }" class="border border-gray-100 rounded-lg bg-white overflow-hidden group hover:border-gray-300 transition-colors">
                                        
                                        <button @click="open = !open" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                                            <span class="font-bold text-gray-800 text-lg group-hover:text-black">{{ $item['question'] }}</span>
                                            <span class="transform transition-transform duration-200 text-gray-400 group-hover:text-black" :class="open ? 'rotate-180' : ''">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </span>
                                        </button>

                                        <div x-show="open" x-collapse class="bg-gray-50">
                                            <div class="p-6 pt-0 text-gray-600 leading-relaxed">
                                                {!! $item['answer'] !!}
                                            </div>
                                        </div>

                                    </div>
                                @endforeach
                            </div>

                        </section>
                    @endforeach
                    
                    {{-- Espaço extra no final para permitir que o último item fique ativo --}}
                    <div class="h-64"></div>
                </div>

            </div>

        </div>
    </div>
</x-layout>