<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Loja - @yield('title', 'Home')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 font-sans antialiased">

    <header id="main-header" class="fixed top-0 w-full z-50 transition-all duration-300 bg-transparent text-white py-6">
        <div class="container mx-auto px-8 flex justify-between items-center relative">
            
            <nav class="flex-1 flex space-x-8 items-center">
                <div class="group inline-block py-2">
                    <a href="#" class="font-medium tracking-wide transition-colors hover:text-gray-300 flex items-center">
                        PRODUTOS
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 mt-0.5 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </a>
                    <div class="absolute left-0 top-full w-full bg-white text-gray-900 shadow-xl invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-300 z-50 border-t">
                        <div class="container mx-auto px-8 py-8 grid grid-cols-4 gap-8">
                            <div>
                                <h4 class="font-bold mb-4 uppercase text-sm tracking-wider">Roupas</h4>
                                <ul class="space-y-2 text-gray-600 text-sm">
                                    <li><a href="#" class="hover:text-black">Camisetas</a></li>
                                    <li><a href="#" class="hover:text-black">Calças</a></li>
                                    <li><a href="#" class="hover:text-black">Casacos</a></li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-bold mb-4 uppercase text-sm tracking-wider">Acessórios</h4>
                                <ul class="space-y-2 text-gray-600 text-sm">
                                    <li><a href="#" class="hover:text-black">Bonés</a></li>
                                    <li><a href="#" class="hover:text-black">Meias</a></li>
                                </ul>
                            </div>
                            <div class="col-span-2 bg-gray-100 h-full rounded-lg p-4 flex items-end">
                                <span class="text-gray-500 text-sm">Destaque da Coleção</span>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="#" class="font-medium tracking-wide transition-colors hover:text-gray-300">OFERTAS</a>
                <a href="#" class="font-medium tracking-wide transition-colors hover:text-gray-300">SOBRE NÓS</a>
            </nav>

            <a href="{{ route('home') }}" id="header-logo" class="text-3xl font-bold tracking-widest uppercase text-white transition-colors flex-none text-center px-4">
                MinhaLoja
            </a>

            <div class="flex-1 flex items-center justify-end space-x-6 header-icons">
                <a href="#" class="hover:text-gray-300 transition-colors" title="Buscar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </a>
                <a href="#" class="hover:text-gray-300 transition-colors" title="Perfil">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </a>
                <a href="#" class="hover:text-gray-300 transition-colors relative" title="Carrinho">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                    <span class="absolute -top-1 -right-2 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">0</span>
                </a>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="bg-neutral-900 text-neutral-400 py-16 mt-20 text-sm">
        <div class="container mx-auto px-8 grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <div>
                <h4 class="text-white font-bold uppercase mb-6 tracking-wider">Sobre a Loja</h4>
                <ul class="space-y-3">
                    <li><a href="#" class="hover:text-white transition">Propósito</a></li>
                    <li><a href="#" class="hover:text-white transition">Sustentabilidade</a></li>
                    <li><a href="#" class="hover:text-white transition">Sobre Nós</a></li>
                    <li><a href="#" class="hover:text-white transition">Carreiras</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold uppercase mb-6 tracking-wider">Ajuda</h4>
                <ul class="space-y-3">
                    <li><a href="#" class="hover:text-white transition">Dúvidas Gerais</a></li>
                    <li><a href="#" class="hover:text-white transition">Entregas</a></li>
                    <li><a href="#" class="hover:text-white transition">Devoluções</a></li>
                    <li><a href="#" class="hover:text-white transition">Fale Conosco</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold uppercase mb-6 tracking-wider">Siga-nos</h4>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-white"><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
                    <a href="#" class="hover:text-white"><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>
                </div>
            </div>
            <div>
                <h4 class="text-white font-bold uppercase mb-6 tracking-wider">Informações Legais</h4>
                <ul class="space-y-3">
                    <li><a href="#" class="hover:text-white transition">Termos de Uso</a></li>
                    <li><a href="#" class="hover:text-white transition">Política de Privacidade</a></li>
                    <li><a href="#" class="hover:text-white transition">Cookies</a></li>
                </ul>
            </div>
        </div>
        <div class="container mx-auto px-8 pt-8 border-t border-neutral-800 flex flex-col md:flex-row justify-between items-center text-xs">
            <p>&copy; {{ date('Y') }} Minha Loja. Todos os direitos reservados.</p>
            <p class="mt-4 md:mt-0">Brasil</p>
        </div>
    </footer>

</body>
</html>