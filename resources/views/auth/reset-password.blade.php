<x-layout>
    {{-- Container Principal: Centraliza o formulário vertical e horizontalmente na tela --}}
    <div class="min-h-screen flex items-center justify-center bg-white py-12 px-4 sm:px-6 lg:px-8">

        {{-- Wrapper do Conteúdo: Define a largura máxima e o espaçamento entre elementos --}}
        <div class="max-w-md w-full space-y-8">
            
            {{-- Cabeçalho da Página --}}
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Redefinir Senha
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Insira sua nova senha abaixo para recuperar o acesso.
                </p>
            </div>

            {{-- Formulário de Redefinição: Utiliza Alpine.js (x-data) para controle de visibilidade da senha --}}
            <form class="mt-8 space-y-6" action="{{ route('password.update') }}" method="POST" x-data="{ showPassword: false }">
                @csrf
                
                {{-- Token de segurança para validação da redefinição de senha --}}
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="space-y-5">
                    
                    {{-- Campo de Email: Configurado como Readonly para garantir a integridade do link enviado --}}
                    <div>
                        <label for="email-address" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input id="email-address" name="email" type="email" autocomplete="email" required readonly
                            class="block w-full h-12 rounded-xl border border-gray-300 bg-gray-100 text-gray-500 shadow-none cursor-not-allowed px-4" 
                            value="{{ $email ?? old('email') }}">
                    </div>

                    {{-- Campo de Nova Senha: Com alternância de visibilidade via Alpine.js --}}
                    <div>
                        <label for="password" class="block text-sm font-bold text-gray-700 mb-1">Nova Senha</label>
                        <div class="relative">
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required 
                                class="block w-full h-12 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all px-4 pr-10" 
                                placeholder="Nova Senha">
                            
                            {{-- Botão para Alternar Visibilidade (Toggle) --}}
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-black cursor-pointer focus:outline-none">
                                {{-- Ícone: Olho Aberto (Senha Oculta) --}}
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                {{-- Ícone: Olho Cortado (Senha Visível) --}}
                                <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Confirmação de Senha: Deve coincidir com o campo anterior (password_confirmation) --}}
                    <div>
                        <label for="password-confirm" class="block text-sm font-bold text-gray-700 mb-1">Confirmar Nova Senha</label>
                        <div class="relative">
                            <input id="password-confirm" name="password_confirmation" :type="showPassword ? 'text' : 'password'" required 
                                class="block w-full h-12 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all px-4 pr-10" 
                                placeholder="Confirmar Senha">
                        </div>
                    </div>
                </div>

                {{-- Exibição de Erros de Validação: Renderiza mensagens caso a validação do Laravel falhe --}}
                @if ($errors->any())
                    <div class="text-red-500 text-sm font-bold bg-red-50 p-3 rounded-lg border border-red-100">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Ação do Formulário: Botão de Submissão --}}
                <div>
                    <button type="submit" class="w-full h-12 px-4 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all cursor-pointer focus:outline-none shadow-sm">
                        REDEFINIR SENHA
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layout>