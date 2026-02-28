@props(['id' => 'auth-slider'])

<div 
    x-data="{ 
        open: false, 
        mode: 'login', 
        email: '',
        password: '',
        code: '',
        name: '',
        last_name: '',
        password_confirmation: '',
        cpf: '',
        phone: '',
        birth_date: '',
        errors: {},
        statusMessage: '',
        loading: false,
        
        // CONTROLE DE VISIBILIDADE DAS SENHAS
        showPassword: false, 
        showPasswordConfirmation: false, // <--- NOVO: Para o campo de confirmação

        toggle() { this.open = !this.open },

        // Auxiliar para Token CSRF
        getToken() {
            return document.querySelector('meta[name=\'csrf-token\']').getAttribute('content');
        },

        // --- MÁSCARAS ---
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
                    { email: this.email, password: this.password },
                    { headers: { 'X-CSRF-TOKEN': this.getToken() } }
                );
                
                if (res.data.status === '2fa_required') {
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
                    { headers: { 'X-CSRF-TOKEN': this.getToken() } }
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
                    name: this.name, last_name: this.last_name, email: this.email, password: this.password,
                    password_confirmation: this.password_confirmation,
                    cpf: this.cpf, phone: this.phone, birth_date: this.birth_date
                }, { headers: { 'X-CSRF-TOKEN': this.getToken() } });


                if (res.data.status === '2fa_required') {
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
                    { email: this.email }, 
                    { headers: { 'X-CSRF-TOKEN': this.getToken() } }
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
         class="fixed inset-0 bg-black/50 backdrop-blur-sm cursor-pointer"></div>

    <div x-show="open" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-xl overflow-y-auto">
        
        <div class="p-6">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-xl font-bold text-gray-900" x-text="mode === 'login' ? 'Entrar' : (mode === 'register' ? 'Criar Conta' : (mode === '2fa' ? 'Verificação' : 'Recuperar Senha'))"></h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-500 cursor-pointer">
                    <span class="sr-only">Fechar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div x-show="mode === 'login'">
                <form @submit.prevent="submitLogin" class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input type="email" x-model="email" class="block w-full h-12 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                        <p x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Senha</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" x-model="password" class="block w-full h-12 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all pr-10">
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-black cursor-pointer focus:outline-none">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <p x-show="errors.password" class="text-red-500 text-xs mt-1" x-text="errors.password"></p>
                    </div>
                    
                    <div class="flex items-center justify-end">
                        <button type="button" @click="mode = 'forgot'; errors = {}; statusMessage = ''" class="text-sm text-gray-600 hover:text-black hover:underline cursor-pointer">Esqueceu a senha?</button>
                    </div>

                    <button type="submit" :disabled="loading" class="w-full flex justify-center items-center h-12 px-4 border border-black rounded-xl shadow-sm text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer focus:outline-none">
                        <span x-show="!loading">ENTRAR</span>
                        <span x-show="loading">CARREGANDO...</span>
                    </button>
                </form>
                
                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-300"></div></div>
                        <div class="relative flex justify-center text-sm"><span class="px-2 bg-white text-gray-500">Ou continue com</span></div>
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('auth.google') }}" class="w-full flex justify-center items-center gap-2 h-12 px-4 border border-gray-300 rounded-xl shadow-sm bg-white text-sm font-medium text-gray-700 hover:border-black hover:text-black transition-all cursor-pointer">
                            <svg class="h-5 w-5" viewBox="0 0 24 24"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/></svg>
                            Google
                        </a>
                    </div>
                </div>
                <p class="mt-8 text-center text-sm text-gray-600">
                    Não tem uma conta? <button @click="mode = 'register'" class="font-bold text-black hover:underline cursor-pointer">Cadastre-se</button>
                </p>
            </div>

            <div x-show="mode === '2fa'" class="space-y-5">
                <p class="text-sm text-gray-600">Enviamos um código para o seu e-mail.</p>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Código de Verificação</label>
                    <input type="text" x-model="code" class="block w-full h-12 text-center tracking-widest text-2xl rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-colors">
                    <p x-show="errors.two_factor_code" class="text-red-500 text-xs mt-1" x-text="errors.two_factor_code"></p>
                </div>
                <button @click="submit2FA" :disabled="loading" class="w-full h-12 px-4 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all cursor-pointer">
                    VALIDAR
                </button>
                <button @click="mode = 'login'" class="w-full mt-2 text-sm text-gray-500 hover:text-black cursor-pointer">Voltar</button>
            </div>

            <div x-show="mode === 'register'">
                <form @submit.prevent="submitRegister" class="space-y-4">
                  <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nome</label>
                            <input type="text" x-model="name" class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                            <p x-show="errors.name" class="text-red-500 text-xs mt-1" x-text="errors.name"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Sobrenome</label>
                            <input type="text" x-model="last_name" class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                            <p x-show="errors.last_name" class="text-red-500 text-xs mt-1" x-text="errors.last_name"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input type="email" x-model="email" class="block w-full h-12 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                        <p x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">CPF</label>
                            <input type="text" x-model="cpf" @input="cpf = formatCPF($el.value)" maxlength="14" placeholder="000.000.000-00" class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                            <p x-show="errors.cpf" class="text-red-500 text-xs mt-1" x-text="errors.cpf"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Telefone</label>
                            <input type="text" x-model="phone" @input="phone = formatPhone($el.value)" maxlength="15" placeholder="(00) 90000-0000" class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                            <p x-show="errors.phone" class="text-red-500 text-xs mt-1" x-text="errors.phone"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Data de Nascimento</label>
                        <input type="date" x-model="birth_date" max="{{ date('Y-m-d', strtotime('-18 years')) }}" class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                        <p x-show="errors.birth_date" class="text-red-500 text-xs mt-1" x-text="errors.birth_date"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Senha</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" x-model="password" 
                                   class="block w-full h-12 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all pr-10">
                            
                            <button type="button" @click="showPassword = !showPassword" 
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-black cursor-pointer focus:outline-none">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <p x-show="errors.password" class="text-red-500 text-xs mt-1" x-text="errors.password"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Confirmar Senha</label>
                        <div class="relative">
                            <input :type="showPasswordConfirmation ? 'text' : 'password'" x-model="password_confirmation" 
                                   class="block w-full h-12 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all pr-10">
                            
                            <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" 
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-black cursor-pointer focus:outline-none">
                                <svg x-show="!showPasswordConfirmation" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="showPasswordConfirmation" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading" class="w-full h-12 px-4 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all cursor-pointer mt-4">
                        CRIAR CONTA
                    </button>
                </form>
                <button @click="mode = 'login'" class="w-full mt-6 text-center text-sm font-bold text-black hover:underline cursor-pointer">Já tenho conta</button>
            </div>
            
            <div x-show="mode === 'forgot'" class="space-y-5">
                <p class="text-sm text-gray-600">Digite seu email para receber um link de redefinição.</p>
                
                <div x-show="statusMessage" class="p-3 bg-green-100 border border-green-200 text-green-700 rounded-xl text-sm font-medium">
                    <span x-text="statusMessage"></span>
                </div>

                <form @submit.prevent="submitForgot">
                    <div class="mb-4">
                         <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                         <input type="email" x-model="email" placeholder="Seu email" 
                                class="block w-full h-12 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                         <p x-show="errors.email" class="text-red-500 text-xs mt-1" x-text="errors.email"></p>
                    </div>
                    <button type="submit" :disabled="loading" class="w-full h-12 px-4 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all cursor-pointer">
                        <span x-show="!loading">ENVIAR LINK</span>
                        <span x-show="loading">ENVIANDO...</span>
                    </button>
                </form>
                <button @click="mode = 'login'" class="w-full mt-2 text-sm text-gray-500 hover:text-black cursor-pointer">Voltar</button>
            </div>

        </div>
    </div>
</div>