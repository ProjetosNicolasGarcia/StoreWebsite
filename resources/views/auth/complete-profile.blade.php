<x-layout>
    <div class="min-h-screen flex items-center justify-center bg-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl ">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Finalizar Cadastro</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Olá, {{ Auth::user()->name }}! Para processar seus pedidos, precisamos de algumas informações adicionais obrigatórias.
                </p>
            </div>

            <form class="mt-8 space-y-6" action="{{ route('auth.update-profile') }}" method="POST"
                  x-data="{
                      cpf: '',
                      phone: '',
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
                
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="cpf" class="block text-sm font-bold text-gray-700 mb-1">CPF</label>
                        <input id="cpf" name="cpf" type="text" required 
                               x-model="cpf" @input="cpf = formatCPF($el.value)" maxlength="14"
                               class="appearance-none rounded-xl relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" 
                               placeholder="000.000.000-00">
                        @error('cpf') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-bold text-gray-700 mb-1">Celular / WhatsApp</label>
                        <input id="phone" name="phone" type="text" required 
                               x-model="phone" @input="phone = formatPhone($el.value)" maxlength="15"
                               class="appearance-none rounded-xl relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" 
                               placeholder="(11) 99999-9999">
                        @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-black hover:bg-gray-800 focus:outline-none transition-colors">
                        Salvar e Continuar
                    </button>
                </div>
                
                <div class="text-center">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-black hover:underline">
                            Cancelar e Sair
                        </button>
                    </form>
                </div>
            </form>
        </div>
    </div>
</x-layout>