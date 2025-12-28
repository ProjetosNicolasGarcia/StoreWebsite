<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Loja - @yield('title', 'Home')</title>
    
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 font-sans antialiased">

    @php
        // Verifica se é a Home Page
        $isHome = request()->routeIs('home');
        
        // Define as classes padrão do CSS (sem Alpine)
        // Se for Home: classes básicas de posicionamento (o Alpine cuidará das cores depois)
        // Se NÃO for Home: forçamos Fundo Branco, Texto Escuro e Sombra direto no HTML
        $headerClasses = $isHome 
            ? 'fixed top-0 w-full z-50 transition-all duration-300' 
            : 'fixed top-0 w-full z-50 transition-all duration-300 bg-white text-gray-900 shadow-md py-4';
    @endphp

    {{-- Header --}}
    <header 
        id="main-header" 
        class="{{ $headerClasses }}"
        
        {{-- Inicializa o Alpine --}}
        {{-- Se NÃO for Home, 'scrolled' já começa true para garantir que ícones internos (como o menu mobile) usem a cor escura --}}
        x-data="{ 
            mobileMenuOpen: false, 
            scrolled: {{ $isHome ? 'false' : 'true' }} 
        }" 

        {{-- BLOCO DE SEGURANÇA: Só adiciona os eventos de scroll e troca de cor SE FOR A HOME --}}
        @if($isHome)
            @scroll.window="scrolled = (window.pageYOffset > 20)"
            :class="scrolled ? 'bg-white text-gray-900 shadow-md py-4' : 'bg-transparent text-white py-6'"
        @endif
    >
        <div class="container mx-auto px-2 sm:px-4 lg:px-8 grid grid-cols-3 items-center relative">
            
            {{-- COLUNA 1: ESQUERDA --}}
            <div class="flex items-center justify-start">
                
                {{-- Botão Mobile --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="lg:hidden p-2 -ml-2 mr-1 focus:outline-none z-20" aria-label="Menu">
                    {{-- Ícone muda de cor baseado no estado 'scrolled' --}}
                    <svg class="h-6 w-6 transition-colors" :class="scrolled ? 'text-gray-900' : 'text-white'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                {{-- Navegação Desktop --}}
                <nav class="hidden lg:flex space-x-8 items-center">
                    <div class="group inline-block py-2 relative">
                        {{-- Link Produtos --}}
                        <a href="#" class="font-medium tracking-wide transition-colors flex items-center" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'">
                            PRODUTOS
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 mt-0.5 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </a>
                        
                        {{-- Dropdown --}}
                        <div class="absolute left-0 top-full w-[200%] min-w-[300px] bg-white shadow-xl invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-300 z-50 border-t border-gray-100 rounded-b-lg mt-2">
                            <div class="p-6">
                                <h4 class="font-bold mb-4 uppercase text-sm tracking-wider !text-black border-b border-gray-100 pb-2">Categorias</h4>
                                <div class="grid grid-cols-2 gap-x-8 gap-y-2">
                                    @if(isset($globalCategories) && $globalCategories->count() > 0)
                                        @foreach($globalCategories as $category)
                                            <a href="{{ route('shop.category', $category->slug) }}" class="block !text-gray-600 hover:!text-red-600 font-medium text-sm transition-colors py-1">
                                                {{ $category->name }}
                                            </a>
                                        @endforeach
                                    @else
                                        <p class="!text-gray-400 text-sm col-span-2 italic">Nenhuma categoria encontrada.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="#" class="font-medium tracking-wide transition-colors" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'">OFERTAS</a>
                    <a href="#" class="font-medium tracking-wide transition-colors" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'">SOBRE NÓS</a>
                </nav>
            </div>

            {{-- COLUNA 2: CENTRO (Logo) --}}
            <a href="{{ route('home') }}" id="header-logo" class="justify-self-center text-lg sm:text-2xl lg:text-3xl font-bold tracking-tight lg:tracking-widest uppercase transition-colors whitespace-nowrap z-10 text-center">
                MinhaLoja
            </a>

            {{-- COLUNA 3: DIREITA (Ícones) --}}
            <div class="flex items-center justify-end space-x-1 sm:space-x-3 lg:space-x-6 header-icons z-20">
                <a href="#" class="transition-colors p-1" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'" title="Buscar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </a>
                <a href="#" class="transition-colors p-1" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'" title="Perfil">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </a>
                <a href="#" class="transition-colors relative p-1" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'" title="Carrinho">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] lg:text-xs font-bold rounded-full h-4 w-4 lg:h-5 lg:w-5 flex items-center justify-center">0</span>
                </a>
            </div>
        </div>

        {{-- Menu Mobile Lateral --}}
        <div x-show="mobileMenuOpen" class="relative z-50 lg:hidden" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="mobileMenuOpen = false"></div>
            <div class="fixed inset-y-0 left-0 w-3/4 max-w-xs bg-white !text-gray-900 shadow-xl overflow-y-auto transform transition-transform duration-300">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-8">
                        <span class="text-xl font-bold uppercase tracking-widest !text-black">Menu</span>
                        <button @click="mobileMenuOpen = false" class="focus:outline-none">
                            <svg class="h-6 w-6 !text-gray-900 hover:text-red-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <nav class="flex flex-col space-y-6">
                        <div x-data="{ expanded: false }">
                            <button @click="expanded = !expanded" class="flex justify-between items-center w-full font-bold uppercase tracking-wide text-lg !text-black border-b border-gray-100 pb-2">
                                Produtos
                                <svg class="h-5 w-5 transition-transform !text-gray-900" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div x-show="expanded" x-collapse class="mt-4 pl-4 flex flex-col space-y-3 text-base">
                                @if(isset($globalCategories) && $globalCategories->count() > 0)
                                    @foreach($globalCategories as $category)
                                        <a href="{{ route('shop.category', $category->slug) }}" class="block !text-black font-medium hover:!text-red-600">
                                            {{ $category->name }}
                                        </a>
                                    @endforeach
                                @else
                                    <span class="!text-gray-500 italic">Sem categorias</span>
                                @endif
                            </div>
                        </div>
                        <a href="#" class="font-bold uppercase tracking-wide text-lg !text-black border-b border-gray-100 pb-2">Ofertas</a>
                        <a href="#" class="font-bold uppercase tracking-wide text-lg !text-black border-b border-gray-100 pb-2">Sobre Nós</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    {{-- Rodapé --}}
    <footer class="bg-gray-100 text-gray-600 py-16 mt-20 text-sm border-t border-gray-200">
        <div class="container mx-auto px-8 grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <div>
                <h4 class="text-gray-900 font-bold uppercase mb-6 tracking-wider">Sobre a Loja</h4>
                <ul class="space-y-3">
                    <li><a href="#" class="hover:text-gray-900 transition">Nossa história</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-gray-900 font-bold uppercase mb-6 tracking-wider">Ajuda</h4>
                <ul class="space-y-3">
                    <li><a href="#" class="hover:text-gray-900 transition">Dúvidas Gerais</a></li>
                    <li><a href="#" class="hover:text-gray-900 transition">Entregas</a></li>
                    <li><a href="#" class="hover:text-gray-900 transition">Devoluções</a></li>
                    <li><a href="#" class="hover:text-gray-900 transition">Fale Conosco</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-gray-900 font-bold uppercase mb-6 tracking-wider">Siga-nos</h4>
                <div class="flex space-x-5">
                    <a href="#" class="text-gray-500 hover:text-pink-600 transition transform hover:-translate-y-1" title="Instagram">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.324a4.162 4.162 0 1 1 0-8.324 4.162 4.162 0 0 1 0 8.324zm6.406-11.845a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z" /></svg>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-black transition transform hover:-translate-y-1" title="X (Twitter)">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-red-600 transition transform hover:-translate-y-1" title="YouTube">
                         <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                     <a href="#" class="text-gray-500 hover:text-black transition transform hover:-translate-y-1" title="TikTok">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                    </a>
                </div>
            </div>
            <div>
                <h4 class="text-gray-900 font-bold uppercase mb-6 tracking-wider">Informações Legais</h4>
                <ul class="space-y-3">
                    <li><a href="#" class="hover:text-gray-900 transition">Termos de Uso</a></li>
                    <li><a href="#" class="hover:text-gray-900 transition">Política de Privacidade</a></li>
                    <li><a href="#" class="hover:text-gray-900 transition">Cookies</a></li>
                </ul>
            </div>
        </div>
        <div class="container mx-auto px-8 pt-8 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center text-xs">
            <p>&copy; {{ date('Y') }} Minha Loja. Todos os direitos reservados.</p>
        </div>
    </footer>

</body>
</html>