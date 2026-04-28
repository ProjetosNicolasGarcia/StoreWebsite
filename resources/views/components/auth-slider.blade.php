@props(['id' => 'auth-slider'])

<div 
    x-data="{ 
        open: false, 
        mode: 'login', 
        
        // FIX: Estados separados para evitar compartilhamento de campos
        loginEmail: '',
        loginPassword: '',
        
        registerEmail: '',
        registerPassword: '',
        password_confirmation: '',
        
        forgotEmail: '',

        code: '',
        name: '',
        last_name: '',
        cpf: '',
        phone: '',
        birth_date: '',
        
        errors: {},
        statusMessage: '',
        loading: false,
        
        showPassword: false, 
        showPasswordConfirmation: false,

        toggle() { this.open = !this.open },

        getToken() {
            return document.querySelector('meta[name=\'csrf-token\']').getAttribute('content');
        },

        // Função para trocar de modo limpando os erros
        switchMode(newMode) {
            this.mode = newMode;
            this.errors = {};
            this.statusMessage = '';
        },

        formatCPF(value) {
            return value.replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1'); 
        },

        formatPhone(value) {
            let v = value.replace(/\D/g, '');
            v = v.replace(/^(\d{2})(\d)/, '($1) $2');
            v = v.replace(/(\d)(\d{4})$/, '$1-$2');
            return v.substring(0, 15); 
        },
        
        async submitLogin() {
            this.loading = true;
            this.errors = {};
            try {
                const res = await axios.post('{{ route('login') }}', 
                    // Usa a variável exclusiva de Login
                    { email: this.loginEmail, password: this.loginPassword },
                    // FIX: Cabeçalho Accept força o Laravel a devolver JSON para engatilhar o 2FA
                    { headers: { 'X-CSRF-TOKEN': this.getToken(), 'Accept': 'application/json' } }
                );
                
                if (res.data && res.data.status === '2fa_required') {
                    this.mode = '2fa';
                } else {
                    window.location.reload();
                }
            } catch (e) {
                if(e.response && e.response.status === 422) {
                    this.errors = e.response.data.errors;
                } else {
                    alert('Erro: ' + (e.response?.data?.message || e.message));
                }
            } finally {
                this.loading = false;
            }
        },

        async submit2FA() {
            this.loading = true;
            this.errors = {};
            try {
                await axios.post('{{ route('auth.two-factor') }}', 
                    { two_factor_code: this.code },
                    { headers: { 'X-CSRF-TOKEN': this.getToken(), 'Accept': 'application/json' } }
                );
                window.location.href = '{{ route('profile.index') }}';
            } catch (e) {
                if(e.response && e.response.status === 422) {
                    this.errors = e.response.data.errors;
                } else {
                    alert('Erro: ' + (e.response?.data?.message || e.message));
                }
            } finally {
                this.loading = false;
            }
        },

        async submitRegister() {
            this.loading = true;
            this.errors = {};
            try {
                const res = await axios.post('{{ route('register') }}', {
                    name: this.name, 
                    last_name: this.last_name, 
                    // Usa a variável exclusiva de Registro
                    email: this.registerEmail, 
                    password: this.registerPassword,
                    password_confirmation: this.password_confirmation,
                    cpf: this.cpf, 
                    phone: this.phone, 
                    birth_date: this.birth_date
                }, { headers: { 'X-CSRF-TOKEN': this.getToken(), 'Accept': 'application/json' } });


                if (res.data && res.data.status === '2fa_required') {
                    this.mode = '2fa';
                } else {
                    window.location.reload();
                }
            } catch (e) {
                if(e.response && e.response.status === 422) {
                    this.errors = e.response.data.errors;
                } else {
                    alert('Erro: ' + (e.response?.data?.message || e.message));
                }
            } finally {
                this.loading = false;
            }
        },

        async submitForgot() {
            this.loading = true;
            this.errors = {};
            this.statusMessage = ''; 
            
            try {
                const res = await axios.post('{{ route('password.email') }}', 
                    { email: this.forgotEmail }, 
                    { headers: { 'X-CSRF-TOKEN': this.getToken(), 'Accept': 'application/json' } }
                );
                
                this.statusMessage = res.data.message; 
                
            } catch (e) {
                if(e.response && e.response.status === 422) {
                    this.errors = e.response.data.errors;
                } else {
                    alert('Erro: ' + (e.response?.data?.message || e.message));
                }
            } finally {
                this.loading = false;
            }
        }
    }"
    @open-auth-slider.window="open = true"
    class="relative z-50"
>
    <div x-show="open" @click="open = false" 
         x-transition.opacity 
         aria-hidden="true"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm cursor-pointer"></div>

    <div x-show="open" 
         role="dialog" 
         aria-modal="true" 
         aria-labelledby="auth-modal-title"
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-xl overflow-y-auto">
        
        <div class="p-6">
            <div class="flex justify-between items-center mb-8">
                <h2 id="auth-modal-title" class="text-xl font-bold text-gray-900" x-text="mode === 'login' ? 'Entrar' : (mode === 'register' ? 'Criar Conta' : (mode === '2fa' ? 'Verificação' : 'Recuperar Senha'))"></h2>
                <button @click="open = false" aria-label="Fechar painel de autenticação" class="text-gray-400 hover:text-gray-500 cursor-pointer focus:outline-none focus:ring-2 focus:ring-black rounded">
                    <span class="sr-only">Fechar</span>
                    <svg aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- FORMULÁRIO DE LOGIN --}}
            <div x-show="mode === 'login'">
                <form @submit.prevent="submitLogin" class="space-y-5">
                    <div>
                        <label for="login-email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input id="login-email" type="email" x-model="loginEmail" 
                               :aria-invalid="errors.email ? 'true' : 'false'"
                               :aria-describedby="errors.email ? 'login-email-error' : null"
                               class="block w-full h-12 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                        <p id="login-email-error" role="alert" x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></p>
                    </div>
                    <div>
                        <label for="login-password" class="block text-sm font-bold text-gray-700 mb-1">Senha</label>
                        <div class="relative">
                            <input id="login-password" :type="showPassword ? 'text' : 'password'" x-model="loginPassword" 
                                   :aria-invalid="errors.password ? 'true' : 'false'"
                                   :aria-describedby="errors.password ? 'login-password-error' : null"
                                   class="block w-full h-12 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all pr-10">
                            
                            <button type="button" @click="showPassword = !showPassword" 
                                    :aria-pressed="showPassword.toString()"
                                    :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-black cursor-pointer focus:outline-none focus:ring-2 focus:ring-black">
                                <svg aria-hidden="true" x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg aria-hidden="true" x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <p id="login-password-error" role="alert" x-show="errors.password" class="text-red-500 text-xs mt-1" x-text="errors.password"></p>
                    </div>
                    
                    <div class="flex items-center justify-end">
                        <button type="button" aria-label="Ir para tela de recuperação de senha" @click="switchMode('forgot')" class="text-sm text-gray-600 hover:text-black hover:underline cursor-pointer focus:outline-none focus:ring-2 focus:ring-black rounded">Esqueceu a senha?</button>
                    </div>

                    <button type="submit" :disabled="loading" class="w-full flex justify-center items-center h-12 px-4 border border-black rounded-none shadow-sm text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                        <span x-show="!loading">ENTRAR</span>
                        <span x-show="loading" aria-hidden="true">CARREGANDO...</span>
                        <span class="sr-only" x-show="loading">Processando login, por favor aguarde.</span>
                    </button>
                </form>
                
                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-300"></div></div>
                        <div class="relative flex justify-center text-sm"><span class="px-2 bg-white text-gray-500">Ou continue com</span></div>
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('auth.google') }}" aria-label="Entrar usando a conta do Google" class="w-full flex justify-center items-center gap-2 h-12 px-4 border border-gray-300 rounded-none shadow-sm bg-white text-sm font-medium text-gray-700 hover:border-black hover:text-black transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            Google
                        </a>
                    </div>
                </div>
                <p class="mt-8 text-center text-sm text-gray-600">
                    Não tem uma conta? <button type="button" @click="switchMode('register')" aria-label="Mudar para tela de cadastro de conta" class="font-bold text-black hover:underline cursor-pointer focus:outline-none focus:ring-2 focus:ring-black rounded">Cadastre-se</button>
                </p>
            </div>

            {{-- 2FA --}}
            <div x-show="mode === '2fa'" class="space-y-5">
                <p class="text-sm text-gray-600" aria-live="polite">Enviamos um código para o seu e-mail.</p>
                <div>
                    <label for="2fa-code" class="block text-sm font-bold text-gray-700 mb-1">Código de Verificação</label>
                    <input id="2fa-code" type="text" x-model="code" 
                           :aria-invalid="errors.two_factor_code ? 'true' : 'false'"
                           :aria-describedby="errors.two_factor_code ? '2fa-error' : null"
                           class="block w-full h-12 text-center tracking-widest text-2xl rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-colors">
                    <p id="2fa-error" role="alert" x-show="errors.two_factor_code" class="text-red-500 text-xs mt-1" x-text="errors.two_factor_code"></p>
                </div>
                <button @click="submit2FA" :disabled="loading" aria-label="Validar código de segurança" class="w-full h-12 px-4 border border-black rounded-none text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                    VALIDAR
                </button>
                <button @click="switchMode('login')" aria-label="Voltar para a tela de login" class="w-full mt-2 text-sm text-gray-500 hover:text-black cursor-pointer focus:outline-none focus:ring-2 focus:ring-black rounded">Voltar</button>
            </div>

            {{-- FORMULÁRIO DE REGISTRO --}}
            <div x-show="mode === 'register'">
                <form @submit.prevent="submitRegister" class="space-y-4">
                  <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="register-name" class="block text-sm font-bold text-gray-700 mb-1">Nome</label>
                            <input id="register-name" type="text" x-model="name" 
                                   :aria-invalid="errors.name ? 'true' : 'false'"
                                   :aria-describedby="errors.name ? 'register-name-error' : null"
                                   class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                            <p id="register-name-error" role="alert" x-show="errors.name" class="text-red-500 text-xs mt-1" x-text="errors.name"></p>
                        </div>
                        <div>
                            <label for="register-lastname" class="block text-sm font-bold text-gray-700 mb-1">Sobrenome</label>
                            <input id="register-lastname" type="text" x-model="last_name" 
                                   :aria-invalid="errors.last_name ? 'true' : 'false'"
                                   :aria-describedby="errors.last_name ? 'register-lastname-error' : null"
                                   class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                            <p id="register-lastname-error" role="alert" x-show="errors.last_name" class="text-red-500 text-xs mt-1" x-text="errors.last_name"></p>
                        </div>
                    </div>
                    <div>
                        <label for="register-email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input id="register-email" type="email" x-model="registerEmail" 
                               :aria-invalid="errors.email ? 'true' : 'false'"
                               :aria-describedby="errors.email ? 'register-email-error' : null"
                               class="block w-full h-12 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                        <p id="register-email-error" role="alert" x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="register-cpf" class="block text-sm font-bold text-gray-700 mb-1">CPF</label>
                            <input id="register-cpf" type="text" x-model="cpf" @input="cpf = formatCPF($el.value)" maxlength="14" placeholder="000.000.000-00" 
                                   :aria-invalid="errors.cpf ? 'true' : 'false'"
                                   :aria-describedby="errors.cpf ? 'register-cpf-error' : null"
                                   class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                            <p id="register-cpf-error" role="alert" x-show="errors.cpf" class="text-red-500 text-xs mt-1" x-text="errors.cpf"></p>
                        </div>
                        <div>
                            <label for="register-phone" class="block text-sm font-bold text-gray-700 mb-1">Telefone</label>
                            <input id="register-phone" type="text" x-model="phone" @input="phone = formatPhone($el.value)" maxlength="15" placeholder="(00) 90000-0000" 
                                   :aria-invalid="errors.phone ? 'true' : 'false'"
                                   :aria-describedby="errors.phone ? 'register-phone-error' : null"
                                   class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                            <p id="register-phone-error" role="alert" x-show="errors.phone" class="text-red-500 text-xs mt-1" x-text="errors.phone"></p>
                        </div>
                    </div>
                    <div>
                        <label for="register-birthdate" class="block text-sm font-bold text-gray-700 mb-1">Data de Nascimento</label>
                        <input id="register-birthdate" type="date" x-model="birth_date" max="{{ date('Y-m-d', strtotime('-18 years')) }}" 
                               :aria-invalid="errors.birth_date ? 'true' : 'false'"
                               :aria-describedby="errors.birth_date ? 'register-birthdate-error' : null"
                               class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                        <p id="register-birthdate-error" role="alert" x-show="errors.birth_date" class="text-red-500 text-xs mt-1" x-text="errors.birth_date"></p>
                    </div>

                    <div>
                        <label for="register-password" class="block text-sm font-bold text-gray-700 mb-1">Senha</label>
                        <div class="relative">
                            <input id="register-password" :type="showPassword ? 'text' : 'password'" x-model="registerPassword" 
                                   :aria-invalid="errors.password ? 'true' : 'false'"
                                   :aria-describedby="errors.password ? 'register-password-error' : null"
                                   class="block w-full h-12 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all pr-10">
                            
                            <button type="button" @click="showPassword = !showPassword" 
                                    :aria-pressed="showPassword.toString()"
                                    :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-black cursor-pointer focus:outline-none focus:ring-2 focus:ring-black">
                                <svg aria-hidden="true" x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg aria-hidden="true" x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <p id="register-password-error" role="alert" x-show="errors.password" class="text-red-500 text-xs mt-1" x-text="errors.password"></p>
                    </div>

                    <div>
                        <label for="register-password-confirm" class="block text-sm font-bold text-gray-700 mb-1">Confirmar Senha</label>
                        <div class="relative">
                            <input id="register-password-confirm" :type="showPasswordConfirmation ? 'text' : 'password'" x-model="password_confirmation" 
                                   class="block w-full h-12 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all pr-10">
                            
                            <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" 
                                    :aria-pressed="showPasswordConfirmation.toString()"
                                    :aria-label="showPasswordConfirmation ? 'Ocultar confirmação de senha' : 'Mostrar confirmação de senha'"
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-black cursor-pointer focus:outline-none focus:ring-2 focus:ring-black">
                                <svg aria-hidden="true" x-show="!showPasswordConfirmation" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg aria-hidden="true" x-show="showPasswordConfirmation" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading" aria-label="Confirmar e criar nova conta" class="w-full h-12 px-4 border border-black rounded-none text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all cursor-pointer mt-4 focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                        CRIAR CONTA
                    </button>
                </form>
                <button type="button" @click="switchMode('login')" aria-label="Mudar para tela de login" class="w-full mt-6 text-center text-sm font-bold text-black hover:underline cursor-pointer focus:outline-none focus:ring-2 focus:ring-black rounded">Já tenho conta</button>
            </div>
            
            {{-- ESQUECEU A SENHA --}}
            <div x-show="mode === 'forgot'" class="space-y-5">
                <p class="text-sm text-gray-600">Digite seu email para receber um link de redefinição.</p>
                
                <div role="status" aria-live="polite">
                    <div x-show="statusMessage" class="p-3 bg-green-100 border border-green-200 text-green-700 rounded-none text-sm font-medium">
                        <span x-text="statusMessage"></span>
                    </div>
                </div>

                <form @submit.prevent="submitForgot">
                    <div class="mb-4">
                         <label for="forgot-email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                         <input id="forgot-email" type="email" x-model="forgotEmail" placeholder="Seu email" 
                                :aria-invalid="errors.email ? 'true' : 'false'"
                                :aria-describedby="errors.email ? 'forgot-email-error' : null"
                                class="block w-full h-12 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                         <p id="forgot-email-error" role="alert" x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></p>
                    </div>
                    <button type="submit" :disabled="loading" aria-label="Enviar link de recuperação de senha" class="w-full h-12 px-4 border border-black rounded-none text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                        <span x-show="!loading">ENVIAR LINK</span>
                        <span x-show="loading" aria-hidden="true">ENVIANDO...</span>
                        <span class="sr-only" x-show="loading">Enviando link, aguarde.</span>
                    </button>
                </form>
                <button type="button" @click="switchMode('login')" aria-label="Voltar para a tela de login" class="w-full mt-2 text-sm text-gray-500 hover:text-black cursor-pointer focus:outline-none focus:ring-2 focus:ring-black rounded">Voltar</button>
            </div>

        </div>
    </div>
</div>