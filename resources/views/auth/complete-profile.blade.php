<x-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <h2 class="mt-6 text-center text-3xl font-black text-gray-900 uppercase tracking-tight">Finalizar Cadastro</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Olá, {{ Auth::user()->name }}! Para processar seus pedidos, precisamos de algumas informações adicionais obrigatórias.
                </p>
            </div>

            <form class="mt-8 space-y-5" action="{{ route('auth.update-profile') }}" method="POST"
                  x-data="{
                      cpf: '{{ old('cpf') }}',
                      phone: '{{ old('phone') }}',
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
                      }
                  }">
                @csrf
                
                {{-- Campos Agrupados (Removida a div com contorno e sombra) --}}
                <div class="space-y-4">
                    <div>
                        <label for="cpf" class="block text-sm font-bold text-gray-700 mb-1">CPF</label>
                        <input id="cpf" name="cpf" type="text" required 
                               x-model="cpf" @input="cpf = formatCPF($el.value)" maxlength="14"
                               class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" 
                               placeholder="000.000.000-00">
                        @error('cpf') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-bold text-gray-700 mb-1">Celular / WhatsApp</label>
                        <input id="phone" name="phone" type="text" required 
                               x-model="phone" @input="phone = formatPhone($el.value)" maxlength="15"
                               class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" 
                               placeholder="(11) 99999-9999">
                        @error('phone') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="birth_date" class="block text-sm font-bold text-gray-700 mb-1">Data de Nascimento</label>
                        <input id="birth_date" name="birth_date" type="date" required 
                               max="{{ date('Y-m-d', strtotime('-18 years')) }}"
                               value="{{ old('birth_date') }}"
                               class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm">
                        @error('birth_date') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Botão Principal Atualizado (Branco com borda preta) --}}
                <div class="pt-2">
                    <button type="submit" class="w-full h-12 flex justify-center items-center px-4 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer uppercase tracking-wide">
                        Salvar e Continuar
                    </button>
                </div>
            </form>
            
            {{-- Formulário de Cancelamento (Separado e em Vermelho) --}}
            <form action="{{ route('logout') }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="w-full flex justify-center items-center py-2 text-sm font-bold text-red-600 hover:text-red-800 hover:underline transition-colors cursor-pointer">
                    Cancelar e Sair
                </button>
            </form>
        </div>
    </div>
</x-layout>