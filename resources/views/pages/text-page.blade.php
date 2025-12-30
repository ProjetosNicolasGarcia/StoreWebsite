<x-layout>
    @section('title', $title)

    <div class="container mx-auto px-4 pt-48 pb-24">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-16 max-w-7xl mx-auto">
            
            {{-- 1. MENU LATERAL --}}
            <aside class="md:col-span-4">
                <div class="sticky top-40 space-y-2">
                    
                    <nav class="flex flex-col space-y-4">
                        @php
                            $currentRoute = Route::currentRouteName();
                            $links = [
                                'pages.terms' => 'Termos de Uso',
                                'pages.privacy' => 'Política de Privacidade',
                                'pages.cookies' => 'Política de Cookies',
                                'pages.accessibility' => 'Acessibilidade',
                            ];
                        @endphp

                        @foreach($links as $route => $label)
                            <a href="{{ route($route) }}" 
                               class="text-xl transition-all duration-200 pl-4 border-l-4
                                      {{ $currentRoute === $route 
                                         ? 'border-black font-black text-black' 
                                         : 'border-transparent font-medium text-gray-400 hover:text-gray-900 hover:border-gray-200' 
                                      }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </nav>

                </div>
            </aside>

            {{-- 2. CONTEÚDO --}}
            <div class="md:col-span-8">
                
                <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight mb-10">
                    {{ $title }}
                </h1>

                <div class="text-gray-700 leading-relaxed text-lg space-y-6 text-justify">
                    {!! $content !!} 
                </div>

            </div>

        </div>
    </div>
</x-layout>