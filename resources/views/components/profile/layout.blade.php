<x-layout>
    @section('title', $title ?? 'Minha Conta')

    {{-- Container Principal (Igual ao da página de texto) --}}
    <div class="container mx-auto px-4 pt-48 pb-24">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-16 max-w-7xl mx-auto">
            
            {{-- 1. MENU LATERAL --}}
            <aside class="md:col-span-4" aria-label="Menu da conta de usuário">
                <div class="sticky top-40 space-y-8">
                    
                    {{-- Saudação Simples --}}
                    <div class="pl-4">
                        <p class="text-sm text-gray-500 uppercase tracking-widest mb-1" aria-hidden="true">Minha Conta</p>
                        <h2 class="text-2xl font-black text-gray-900" aria-label="Painel da conta de {{ Auth::user()->name }}">{{ Auth::user()->name }}</h2>
                    </div>

                    <nav class="flex flex-col space-y-4" aria-label="Navegação do perfil">
                        @php
                            $currentRoute = Route::currentRouteName();
                            $menuItems = [
                                'profile.index' => 'Meus Dados',
                                'profile.orders' => 'Meus Pedidos',
                                'profile.addresses' => 'Endereços',
                                'profile.favorites' => 'Favoritos', // <-- NOVA ABA ADICIONADA AQUI
                            ];
                        @endphp

                        {{-- Links Principais --}}
                        @foreach($menuItems as $route => $label)
                            <a href="{{ route($route) }}" 
                               @if($currentRoute === $route) aria-current="page" @endif
                               class="text-xl transition-all duration-200 pl-4 border-l-4 block focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2
                                      {{ $currentRoute === $route 
                                         ? 'border-black font-black text-black' 
                                         : 'border-transparent font-medium text-gray-400 hover:text-gray-900 hover:border-gray-200' 
                                      }}">
                                {{ $label }}
                            </a>
                        @endforeach

                        {{-- Link de Ajuda --}}
                        <a href="{{ route('pages.contact') }}" 
                           class="text-xl transition-all duration-200 pl-4 border-l-4 border-transparent font-medium text-gray-400 hover:text-gray-900 hover:border-gray-200 block focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                            Precisa de Ajuda?
                        </a>

                        {{-- Botão Sair (Estilizado como link) --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" 
                                    aria-label="Sair da sua conta"
                                    class="text-xl transition-all duration-200 pl-4 border-l-4 border-transparent font-medium text-red-400 hover:text-red-700 hover:border-red-200 w-full text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                                Sair
                            </button>
                        </form>
                    </nav>

                </div>
            </aside>

            {{-- 2. CONTEÚDO PRINCIPAL --}}
            <main class="md:col-span-8" role="main" aria-label="Conteúdo Principal">
                
                {{-- Mensagens de Feedback --}}
                @if (session('success'))
                    <div aria-live="polite">
                        <div class="mb-8 p-4 bg-green-50 border-l-4 border-green-500 text-green-700" role="status">
                            <span class="font-bold block mb-1">Sucesso</span>
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div aria-live="assertive">
                        <div class="mb-8 p-4 bg-red-50 border-l-4 border-red-500 text-red-700" role="alert">
                            <span class="font-bold block mb-1">Atenção</span>
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Slot de Conteúdo (Sem box branco, direto no fundo para manter o padrão visual) --}}
                <div class="text-gray-900">
                    {{ $slot }}
                </div>
            </main>

        </div>
    </div>
</x-layout>