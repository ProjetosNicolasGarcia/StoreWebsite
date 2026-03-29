<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Minha Loja - @yield('title', 'Home')</title>

    @stack('head')
    
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.8/dist/cdn.min.js" integrity="sha256-yA1tVqQv5xP7+pWf9hGgT7gK8L8j4y8q3qHwXwW1Z3A=" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/axios@1.8/dist/axios.min.js" crossorigin="anonymous"></script>

   <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Cutive+Mono&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Cutive+Mono&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="bg-white text-gray-900 font-sans antialiased" 
      x-data="{ cartOpen: {{ session('open_cart') ? 'true' : 'false' }}, categoryMenuOpen: false, cartLoading: false }"
      @open-cart.window="cartOpen = true"
      @start-cart-loading.window="cartLoading = true"
      @update-cart-count.window="cartLoading = false">

    @if (session('status'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition.opacity
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-5 left-1/2 transform -translate-x-1/2 z-[100] bg-black text-white px-6 py-3 rounded-full shadow-lg flex items-center gap-3">
            
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium text-sm">{{ session('status') }}</span>
            <button @click="show = false" class="ml-2 text-gray-400 hover:text-white focus:outline-none">&times;</button>
        </div>
    @endif

    @php
        $isHome = request()->routeIs('home');
        $headerClasses = $isHome 
            ? 'fixed top-0 w-full z-50 transition-all duration-300' 
            : 'fixed top-0 w-full z-50 transition-all duration-300 bg-white text-gray-900 shadow-md py-4';
    @endphp

    <header id="main-header" class="{{ $headerClasses }}"
        x-data="{ 
            mobileMenuOpen: false, 
            scrolled: {{ $isHome ? 'false' : 'true' }},
            searchOpen: false,
            query: '',
            suggestions: [],
            async fetchSuggestions() {
                if (this.query.length < 2) { this.suggestions = []; return; }
                try {
                    const response = await fetch(`{{ route('shop.suggestions') }}?q=${this.query}`);
                    this.suggestions = await response.json();
                } catch (error) { console.error(error); }
            },
            init() {
                @if($isHome)
                    this.scrolled = (window.pageYOffset > 20);
                @endif
            }
        }" 
        @if($isHome) @scroll.window="scrolled = (window.pageYOffset > 20)" :class="scrolled ? 'bg-white text-gray-900 shadow-md py-4' : 'bg-transparent text-white py-6'" @endif
    >
        <div class="container mx-auto px-2 sm:px-4 lg:px-8 relative" x-show="!searchOpen">
            <div class="grid grid-cols-3 items-center">
                
                {{-- ESQUERDA --}}
                <div class="flex items-center justify-start gap-4">
                    
                    {{-- [COLOR FIX] Trava explicitamente as cores do hover --}}
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="lg:hidden p-2 -ml-2 mr-1 focus:outline-none z-20 transition-all duration-200 hover:scale-110 cursor-pointer" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'" aria-label="Abrir menu mobile">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>

                    <div class="hidden lg:flex items-center gap-6">
                        {{-- [COLOR FIX] Trava explicitamente as cores do hover --}}
                        <button @click="categoryMenuOpen = true" type="button" class="flex items-center gap-2 font-bold tracking-wide transition-all duration-200 hover:underline underline-offset-4 cursor-pointer group focus:outline-none" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'">
                            <span>PRODUTOS</span>
                        </button>

                        <nav class="flex space-x-6 items-center">
                            {{-- [COLOR FIX] Trava explicitamente as cores do hover --}}
                            <a href="{{ route('shop.offers') }}" class="font-medium tracking-wide transition-all duration-200 hover:underline underline-offset-4 cursor-pointer" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'">OFERTAS</a>
                            <a href="#" class="font-medium tracking-wide transition-all duration-200 hover:underline underline-offset-4 cursor-pointer" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'">SOBRE NÓS</a>
                        </nav>
                    </div>
                </div>

                {{-- CENTRO --}}
                {{-- [COLOR FIX] Trava explicitamente as cores do hover --}}
                <a href="{{ route('home') }}" id="header-logo" class="justify-self-center text-lg sm:text-2xl lg:text-3xl font-bold tracking-tight lg:tracking-widest uppercase transition-all duration-200 hover:scale-105 cursor-pointer whitespace-nowrap z-10 text-center" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'">NOME DA LOJA</a>

                {{-- DIREITA --}}
                <div class="flex items-center justify-end space-x-2 sm:space-x-4 lg:space-x-6 header-icons z-20">
                    
                    {{-- [COLOR FIX] Trava explicitamente as cores do hover --}}
                    <button type="button" aria-label="Buscar produtos" @click="searchOpen = true; $nextTick(() => $refs.searchInputDesktop.focus())" class="transition-all duration-200 hover:scale-110 p-1 focus:outline-none cursor-pointer" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </button>

                    @auth
                        {{-- [COLOR FIX] Trava explicitamente as cores do hover --}}
                        <a href="{{ route('profile.index') }}" aria-label="Perfil" class="transition-all duration-200 hover:scale-110 p-1 flex items-center gap-1 focus:outline-none cursor-pointer" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </a>
                    @else
                        {{-- [COLOR FIX] Trava explicitamente as cores do hover --}}
                        <button type="button" aria-label="Login" @click="$dispatch('open-auth-slider')" class="transition-all duration-200 hover:scale-110 p-1 focus:outline-none cursor-pointer" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </button>
                    @endauth

                    {{-- [COLOR FIX] Trava explicitamente as cores do hover --}}
                    <button type="button" aria-label="Carrinho" @click="cartOpen = true" class="transition-all duration-200 hover:scale-110 relative p-1 focus:outline-none cursor-pointer" :class="scrolled ? 'text-gray-900 hover:text-gray-900' : 'text-white hover:text-white'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        
                        <span x-data="{ count: {{ isset($globalCartItems) ? $globalCartItems->count() : 0 }} }"
                              @update-cart-count.window="count = $event.detail.count !== undefined ? $event.detail.count : 0"
                              x-show="count > 0"
                              x-text="count"
                              x-cloak
                              x-transition
                              class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] lg:text-xs font-bold rounded-full h-4 w-4 lg:h-5 lg:w-5 flex items-center justify-center">
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- BUSCA --}}
        <div x-cloak x-show="searchOpen" x-transition class="fixed top-0 left-0 w-full z-50 bg-white flex items-center justify-center px-4 shadow-md h-18">
            <div class="container mx-auto max-w-4xl relative w-full">
                <form action="{{ route('shop.search') }}" method="GET" class="w-full flex items-center">
                    <svg class="h-8 w-8 text-gray-900 mr-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input x-ref="searchInputDesktop" type="text" name="q" x-model="query" @input.debounce.300ms="fetchSuggestions()" placeholder="O que você está procurando?" class="w-full bg-transparent border-none text-gray-900 text-2xl font-medium focus:ring-0 focus:outline-none h-16" autocomplete="off">
                    <button type="button" @click="searchOpen = false; query = ''; suggestions = []" class="ml-4 text-gray-500 hover:text-black transition p-2"><svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    
                    <div x-cloak x-show="suggestions.length > 0" @click.outside="suggestions = []" class="absolute top-full left-0 right-0 mt-4 bg-white border-t border-gray-100 shadow-xl rounded-b-lg overflow-hidden z-50">
                        <ul>
                            <template x-for="product in suggestions" :key="product.id">
                                <li>
                                    <a :href="'/product/' + product.slug" class="flex items-center p-4 hover:bg-gray-50 transition border-b border-gray-50 last:border-0 group">
                                        <div class="h-12 w-12 flex-shrink-0 bg-gray-100 rounded overflow-hidden mr-4 border border-gray-200">
                                            <img :src="'/storage/' + product.image_url" :alt="product.name" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                        </div>
                                        <div class="flex flex-col">
                                            <p class="text-base font-bold text-gray-900 group-hover:text-black" x-text="product.name"></p>
                                            
                                            <template x-if="product.on_sale">
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <p class="text-xs text-gray-400 line-through">
                                                        R$ <span x-text="new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(product.original_price)"></span>
                                                    </p>
                                                    <p class="text-sm font-bold text-red-600">
                                                        R$ <span x-text="new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(product.price)"></span>
                                                    </p>
                                                </div>
                                            </template>

                                            <template x-if="!product.on_sale">
                                                <p class="text-sm text-gray-500 mt-0.5">
                                                    R$ <span x-text="new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(product.price)"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </form>
            </div>
        </div>

        {{-- MOBILE MENU --}}
        <div x-cloak x-show="mobileMenuOpen" class="relative z-50 lg:hidden">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="mobileMenuOpen = false"></div>
            
            <div class="fixed inset-y-0 left-0 w-full sm:w-[350px] bg-white !text-gray-900 shadow-xl overflow-y-auto transform transition-transform duration-300">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-8">
                        <span class="text-xl font-bold uppercase tracking-widest !text-black">Menu</span>
                        <button @click="mobileMenuOpen = false" class="focus:outline-none"><svg class="h-6 w-6 text-gray-900 hover:text-black transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                    <nav class="flex flex-col space-y-6">
                        <button @click="mobileMenuOpen = false; categoryMenuOpen = true" class="w-full text-left font-bold uppercase tracking-wide text-lg !text-black border-b border-gray-100 pb-2 flex justify-between items-center hover:underline underline-offset-4 transition-all">
                            PRODUTOS
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                        <a href="{{ route('shop.offers') }}" class="font-bold uppercase tracking-wide text-lg !text-black border-b border-gray-100 pb-2 hover:underline underline-offset-4 transition-all cursor-pointer">Ofertas</a>
                        <a href="#" class="font-bold uppercase tracking-wide text-lg !text-black border-b border-gray-100 pb-2 hover:underline underline-offset-4 transition-all cursor-pointer">Sobre Nós</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="{{ $isHome ? '' : 'pt-20 md:pt-24' }}">
        {{ $slot }}
    </main>

    {{-- MENU LATERAL DE CATEGORIAS --}}
    <div x-cloak x-show="categoryMenuOpen" 
         x-transition.opacity 
         class="fixed inset-0 bg-black/50 z-[60] backdrop-blur-sm" 
         @click="categoryMenuOpen = false"></div>

    <div x-cloak x-show="categoryMenuOpen" 
         x-transition:enter="transition ease-out duration-300 transform" 
         x-transition:enter-start="-translate-x-full" 
         x-transition:enter-end="translate-x-0" 
         x-transition:leave="transition ease-in duration-300 transform" 
         x-transition:leave-start="translate-x-0" 
         x-transition:leave-end="-translate-x-full" 
         class="fixed inset-y-0 left-0 w-full sm:w-[350px] bg-white z-[70] shadow-2xl flex flex-col h-full overflow-hidden">

        <div class="px-6 py-6 lg:py-5 border-b border-gray-100 flex items-center justify-between bg-white text-black">
            <div class="flex items-center gap-2">
                <h2 class="text-xl lg:text-lg font-bold uppercase tracking-widest">PRODUTOS</h2>
            </div>
            <button @click="categoryMenuOpen = false" class="text-black hover:text-gray-600 transition focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            @if(isset($globalCategories) && $globalCategories->count() > 0)
                <ul class="flex flex-col">
                    @foreach($globalCategories as $category)
                        <x-category-item :category="$category" />
                    @endforeach
                </ul>
            @else
                <div class="p-8 text-center text-gray-500 italic">
                    Nenhuma categoria encontrada.
                </div>
            @endif
        </div>
    </div>

    <div x-cloak x-show="cartOpen" x-transition.opacity class="fixed inset-0 bg-black/50 z-[60] backdrop-blur-sm" @click="cartOpen = false"></div>

    @livewire('cart-sidebar')

    <footer class="bg-[#080808] text-gray-300 py-16 mt-20 text-sm border-t border-[#080808]">
        <div class="container mx-auto px-8 grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <div>
                <h4 class="text-white font-bold uppercase mb-6 tracking-wider">Sobre a Loja</h4>
                <ul class="space-y-3">
                    <li><a href="#" class="hover:text-white transition">Nossa história</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold uppercase mb-6 tracking-wider">Ajuda</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('pages.faq') }}" class="hover:text-white transition">Dúvidas Gerais (FAQ)</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-white transition">Fale Conosco</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold uppercase mb-6 tracking-wider">Siga-nos</h4>
                 <div class="flex space-x-5">
                    <a href="https://www.instagram.com/" class="text-white hover:text-pink-500 transition transform hover:-translate-y-1" title="Instagram">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.324a4.162 4.162 0 1 1 0-8.324 4.162 4.162 0 0 1 0 8.324zm6.406-11.845a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z" /></svg>
                    </a>
                    
                    <a href="https://x.com/" class="text-white hover:text-white transition transform hover:-translate-y-1" title="X (Twitter)">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>

                    <a href="https://www.youtube.com/@" class="text-white hover:text-red-500 transition transform hover:-translate-y-1" title="YouTube">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>

                    <a href="https://www.tiktok.com/@" class="text-white hover:text-white transition transform hover:-translate-y-1" title="TikTok">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                    </a>
                </div>
            </div>
            <div>
                <h4 class="text-white font-bold uppercase mb-6 tracking-wider">Informações Legais</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('pages.terms') }}" class="hover:text-white transition">Termos de Uso</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-white transition">Política de Privacidade</a></li>
                    <li><a href="{{ route('pages.cookies') }}" class="hover:text-white transition">Política de cookies</a></li>
                    <li><a href="{{ route('pages.accessibility') }}" class="hover:text-white transition">Acessibilidade</a></li>
                </ul>
            </div>
        </div>
        <div class="container mx-auto px-8 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-300">
            <p>&copy; {{ date('Y') }} Minha Loja. Todos os direitos reservados.</p>
        </div>
    </footer>

    <x-auth-slider />
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            if (token) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            }
        });

        window.addEventListener('beforeunload', function() { sessionStorage.setItem('scrollPosition', window.scrollY); });
        @if(session('open_cart'))
            document.addEventListener('DOMContentLoaded', function() {
                const scrollPos = sessionStorage.getItem('scrollPosition');
                if (scrollPos) {
                    window.scrollTo({ top: parseInt(scrollPos), behavior: 'instant' });
                    sessionStorage.removeItem('scrollPosition'); 
                }
            });
        @endif
    </script>
    @livewireScripts
</body>
</html>