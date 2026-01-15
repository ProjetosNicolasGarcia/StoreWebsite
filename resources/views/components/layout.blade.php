<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    {{-- 1. Meta Tag de Segurança (CSRF) - OBRIGATÓRIA --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Minha Loja - @yield('title', 'Home')</title>
    
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{-- Axios --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    {{-- 2. Configuração do Axios para enviar o Token --}}
    <script>
        // Espera a página carregar para configurar o Axios
        document.addEventListener('DOMContentLoaded', function () {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            if (token) {
                // Diz para o Axios: "Em todo envio, mande esse token junto!"
                axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            } else {
                console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
            }
        });
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- 
    ALTERAÇÃO 1: Adicionado x-data no body para controlar o carrinho globalmente.
    Verifica se a sessão pediu para abrir o carrinho (ex: após adicionar item).
--}}
<body class="bg-white text-gray-900 font-sans antialiased" 
      x-data="{ cartOpen: {{ session('open_cart') ? 'true' : 'false' }} }">

    @php
        $isHome = request()->routeIs('home');
        $headerClasses = $isHome 
            ? 'fixed top-0 w-full z-50 transition-all duration-300' 
            : 'fixed top-0 w-full z-50 transition-all duration-300 bg-white text-gray-900 shadow-md py-4';
    @endphp

    <header 
        id="main-header" 
        class="{{ $headerClasses }}"
        
        {{-- Lógica Alpine do Header (Mantida) --}}
        x-data="{ 
            mobileMenuOpen: false, 
            scrolled: {{ $isHome ? 'false' : 'true' }},
            searchOpen: false,
            query: '',
            suggestions: [],
            
            async fetchSuggestions() {
                if (this.query.length < 2) {
                    this.suggestions = [];
                    return;
                }
                try {
                    const response = await fetch(`{{ route('shop.suggestions') }}?q=${this.query}`);
                    this.suggestions = await response.json();
                } catch (error) {
                    console.error('Erro ao buscar sugestões:', error);
                }
            }
        }" 

        @if($isHome)
            @scroll.window="scrolled = (window.pageYOffset > 20)"
            :class="scrolled ? 'bg-white text-gray-900 shadow-md py-4' : 'bg-transparent text-white py-6'"
        @endif
    >
        {{-- CONTAINER PRINCIPAL DO CABEÇALHO --}}
        <div class="container mx-auto px-2 sm:px-4 lg:px-8 relative" x-show="!searchOpen">
            
            <div class="grid grid-cols-3 items-center">
                
                {{-- ESQUERDA: Menu Mobile e Nav Desktop --}}
                <div class="flex items-center justify-start">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="lg:hidden p-2 -ml-2 mr-1 focus:outline-none z-20" aria-label="Menu">
                        <svg class="h-6 w-6 transition-colors" :class="scrolled ? 'text-gray-900' : 'text-white'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <nav class="hidden lg:flex space-x-8 items-center">
                        <div class="group inline-block py-2 relative">
                            <a href="#" class="font-medium tracking-wide transition-colors flex items-center" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'">
                                PRODUTOS
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 mt-0.5 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </a>
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
                        <a href="{{ route('shop.offers') }}" class="font-medium tracking-wide transition-colors" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'">OFERTAS</a>
                        <a href="#" class="font-medium tracking-wide transition-colors" :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'">SOBRE NÓS</a>
                    </nav>
                </div>

                {{-- CENTRO: Logo --}}
                <a href="{{ route('home') }}" id="header-logo" class="justify-self-center text-lg sm:text-2xl lg:text-3xl font-bold tracking-tight lg:tracking-widest uppercase transition-colors whitespace-nowrap z-10 text-center">
                    MinhaLoja
                </a>

                {{-- DIREITA: Ícones --}}
                <div class="flex items-center justify-end space-x-2 sm:space-x-4 lg:space-x-6 header-icons z-20">
                    <button type="button" 
                            @click="searchOpen = true; $nextTick(() => $refs.searchInputDesktop.focus())"
                            class="transition-colors p-1 focus:outline-none" 
                            :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'" 
                            title="Buscar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                   {{-- LOGICA DO USUÁRIO (LOGIN / LOGOUT) --}}

@auth
    {{-- Usuário Logado: Mostra menu dropdown --}}
    <div class="relative" x-data="{ userMenuOpen: false }">
        <button @click="userMenuOpen = !userMenuOpen" 
                class="transition-colors p-1 flex items-center gap-1 focus:outline-none" 
                :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'">
            
            {{-- Apenas o ícone --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            
            {{-- REMOVIDO: O span com o nome foi apagado aqui --}}
        </button>

        {{-- Dropdown de Logout (Mantido) --}}
        <div x-show="userMenuOpen" 
             @click.outside="userMenuOpen = false"
             x-transition
             style="display: none;"
             class="absolute right-0 mt-2 w-40 bg-white shadow-xl rounded-md border border-gray-100 z-50">
            <div class="py-1">
                {{-- Opcional: Mostrar o nome aqui dentro do menu para confirmação --}}
                <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-100">
                    {{ auth()->user()->name }}
                </div>
                
                <form method="POST" action="{{ route('store.logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-red-600">
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
@else
    {{-- Visitante: Botão que abre o Slider de Login (Mantido igual) --}}
    <button type="button" 
            @click="$dispatch('open-auth-slider')" 
            class="transition-colors p-1 focus:outline-none" 
            :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'"
            title="Minha Conta">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
    </button>
@endauth
                    {{-- 
                        ALTERAÇÃO 2: Botão do Carrinho 
                        - Mudado de <a> para <button> para ação de clique.
                        - Adicionado @click="cartOpen = true".
                    --}}
                    <button type="button" 
                            @click="cartOpen = true"
                            class="transition-colors relative p-1 focus:outline-none" 
                            :class="scrolled ? 'hover:text-gray-600' : 'hover:text-gray-300'">
                        
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        
                        {{-- ALTERAÇÃO 3: Lógica do Badge de Contagem --}}
                        @if(isset($globalCartItems) && $globalCartItems->count() > 0)
                            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] lg:text-xs font-bold rounded-full h-4 w-4 lg:h-5 lg:w-5 flex items-center justify-center">
                                {{ $globalCartItems->count() }}
                            </span>
                        @else
                            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] lg:text-xs font-bold rounded-full h-4 w-4 lg:h-5 lg:w-5 flex items-center justify-center" style="display: none;">0</span>
                        @endif
                    </button>
                </div>
            </div>
        </div>

        {{-- OVERLAY DE PESQUISA (MANTIDO IGUAL) --}}
        <div x-show="searchOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-0 left-0 w-full z-50 bg-white flex items-center justify-center px-4 shadow-md h-18"
             style="display: none;">
             
            <div class="container mx-auto max-w-4xl relative w-full">
                <form action="{{ route('shop.search') }}" method="GET" class="w-full flex items-center">
                    <svg class="h-8 w-8 text-gray-900 mr-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    <input x-ref="searchInputDesktop"
                           type="text" 
                           name="q" 
                           x-model="query"
                           @input.debounce.300ms="fetchSuggestions()"
                           placeholder="O que você está procurando?" 
                           class="w-full bg-transparent border-none text-gray-900 text-2xl font-medium focus:ring-0 focus:outline-none placeholder-gray-400 h-16" 
                           autocomplete="off"
                    >

                    <button type="button" @click="searchOpen = false; query = ''; suggestions = []" class="ml-4 text-gray-500 hover:text-red-600 transition p-2 flex-shrink-0">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div x-show="suggestions.length > 0" 
                         @click.outside="suggestions = []"
                         class="absolute top-full left-0 right-0 mt-4 bg-white border-t border-gray-100 shadow-xl rounded-b-lg overflow-hidden z-50">
                        <ul>
                            <template x-for="product in suggestions" :key="product.id">
                                <li>
                                    <a :href="'/product/' + product.slug" class="flex items-center p-4 hover:bg-gray-50 transition border-b border-gray-50 last:border-0 group">
                                        <div class="h-12 w-12 flex-shrink-0 bg-gray-100 rounded overflow-hidden mr-4 border border-gray-200">
                                            <img :src="'/storage/' + product.image_url" class="h-full w-full object-cover">
                                        </div>
                                        <div>
                                            <p class="text-base font-bold text-gray-900 group-hover:text-black" x-text="product.name"></p>
                                            <p class="text-sm text-gray-500">R$ <span x-text="new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(product.base_price)"></span></p>
                                        </div>
                                    </a>
                                </li>
                            </template>
                        </ul>
                        <div class="bg-gray-50 p-4 text-center">
                            <button type="submit" class="text-sm font-black text-gray-900 hover:underline uppercase tracking-widest">
                                Ver todos os resultados
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Menu Mobile Lateral (MANTIDO IGUAL) --}}
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
                            <div x-show="expanded" class="mt-4 pl-4 flex flex-col space-y-3 text-base">
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

    {{-- 
        ALTERAÇÃO 4: Menu Lateral do Carrinho (Drawer)
        Inserido aqui para não conflitar com z-index do header e funcionar globalmente
    --}}
    
    {{-- Overlay Escuro --}}
    <div x-show="cartOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 z-[60] backdrop-blur-sm"
         @click="cartOpen = false"
         style="display: none;"></div>

    {{-- Gaveta --}}
    <div x-show="cartOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-full md:w-[450px] bg-white z-[70] shadow-2xl flex flex-col h-full"
         style="display: none;">

        {{-- Cabeçalho do Drawer --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
            <h2 class="text-xl font-black uppercase tracking-tight">Seu Carrinho</h2>
            <button @click="cartOpen = false" class="text-gray-400 hover:text-black transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Lista de Itens --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            @if(isset($globalCartItems) && $globalCartItems->count() > 0)
                @foreach($globalCartItems as $item)
                    <div class="flex gap-4">
                        <div class="w-20 h-24 bg-gray-50 rounded-md overflow-hidden flex-shrink-0">
                            <img src="{{ Storage::url($item->product->image_url) }}" class="w-full h-full object-contain p-2">
                        </div>
                        <div class="flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-sm uppercase tracking-wide text-gray-900 line-clamp-2">
                                        {{ $item->product->name }}
                                    </h3>
                                    <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456-1.21L9.264 9m9.968-3.21c-3.185-.81-7.156-.81-10.34 0m9.68 0H14.16M13.68 3.5c-.32-.61-.925-1.02-1.63-1.02H11.95c-.705 0-1.31.41-1.63 1.02M10.34 4.79H9.264" /></svg>
                                        </button>
                                    </form>
                                </div>
                                @if($item->product->category)
                                    <p class="text-xs text-gray-500 mt-1">{{ $item->product->category->name }}</p>
                                @endif
                            </div>
                            <div class="flex items-end justify-between mt-2">
                                {{-- Controle Quantidade --}}
                                <div class="flex items-center border border-gray-200 rounded">
                                    <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="decrease">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">-</button>
                                    </form>
                                    <span class="w-8 h-8 flex items-center justify-center text-xs font-bold">{{ $item->quantity }}</span>
                                    <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="increase">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">+</button>
                                    </form>
                                </div>
                                {{-- Preço --}}
                                <div class="text-right">
                                    @if($item->product->isOnSale())
                                        <p class="text-xs text-gray-400 line-through">R$ {{ number_format($item->product->base_price * $item->quantity, 2, ',', '.') }}</p>
                                        <p class="font-bold text-red-600">R$ {{ number_format($item->product->sale_price * $item->quantity, 2, ',', '.') }}</p>
                                    @else
                                        <p class="font-bold text-gray-900">R$ {{ number_format($item->product->base_price * $item->quantity, 2, ',', '.') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="h-full flex flex-col items-center justify-center text-center space-y-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-16 h-16 text-gray-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    <p class="text-gray-500">Seu carrinho está vazio.</p>
                    <button @click="cartOpen = false" class="text-black font-bold border-b border-black hover:text-gray-600 hover:border-gray-600 transition">
                        Continuar comprando
                    </button>
                </div>
            @endif
        </div>

        @if(isset($globalCartItems) && $globalCartItems->count() > 0)
            <div class="p-6 bg-gray-50 border-t border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-500 uppercase text-xs tracking-widest">Subtotal</span>
                    <span class="font-black text-xl">R$ {{ number_format($globalCartTotal ?? 0, 2, ',', '.') }}</span>
                </div>
                <button class="w-full bg-black text-white py-4 font-bold uppercase tracking-widest hover:bg-gray-800 transition">
                    Finalizar Compra
                </button>
            </div>
        @endif
    </div>

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
                    <li><a href="{{ route('pages.faq') }}" class="hover:text-gray-900 transition">Dúvidas Gerais (FAQ)</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-gray-900 transition">Fale Conosco</a></li>
                </ul>
            </div>
         <div>
    <h4 class="text-gray-900 font-bold uppercase mb-6 tracking-wider">Siga-nos</h4>
    <div class="flex space-x-5">
        {{-- Instagram --}}
            <a href="https://www.instagram.com/" class="text-gray-500 hover:text-pink-600 transition transform hover:-translate-y-1" title="Instagram">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.324a4.162 4.162 0 1 1 0-8.324 4.162 4.162 0 0 1 0 8.324zm6.406-11.845a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z" /></svg>
            </a>
            
            {{-- X (Twitter) --}}
            <a href="https://x.com/" class="text-gray-500 hover:text-black transition transform hover:-translate-y-1" title="X (Twitter)">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>

            {{-- YouTube (Novo) --}}
            <a href="https://www.youtube.com/@" class="text-gray-500 hover:text-red-600 transition transform hover:-translate-y-1" title="YouTube">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            </a>

            {{-- TikTok (Novo) --}}
            <a href="https://www.tiktok.com/@" class="text-gray-500 hover:text-black transition transform hover:-translate-y-1" title="TikTok">
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
            </a>
        </div>
    </div>

            <div>
                <h4 class="text-gray-900 font-bold uppercase mb-6 tracking-wider">Informações Legais</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('pages.terms') }}" class="hover:text-gray-900 transition">Termos de Uso</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-gray-900 transition">Política de Privacidade</a></li>
                    <li><a href="{{ route('pages.cookies') }}" class="hover:text-gray-900 transition">Política de cookies</a></li>
                    <li><a href="{{ route('pages.accessibility') }}" class="hover:text-gray-900 transition">Acessibilidade</a></li>
                </ul>
            </div>
        </div>
        <div class="container mx-auto px-8 pt-8 border-t border-gray-200 flex flex-col md:flex-row justify-between items-center text-xs">
            <p>&copy; {{ date('Y') }} Minha Loja. Todos os direitos reservados.</p>
        </div>
    </footer>
    <x-auth-slider />
    <script>
        // 1. Salva a posição da rolagem antes da página recarregar
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });

        // 2. Verifica se devemos restaurar a posição
        // A sessão 'open_cart' só existe quando o controller redireciona após adicionar um item
        @if(session('open_cart'))
            document.addEventListener('DOMContentLoaded', function() {
                const scrollPos = sessionStorage.getItem('scrollPosition');
                if (scrollPos) {
                    window.scrollTo({
                        top: parseInt(scrollPos),
                        behavior: 'instant' // Usa 'instant' para não ver a tela descendo
                    });
                    // Limpa para não afetar outras navegações
                    sessionStorage.removeItem('scrollPosition'); 
                }
            });
        @endif
    </script>
</body>
</html>