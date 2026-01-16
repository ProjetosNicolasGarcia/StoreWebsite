<x-profile.layout title="Meus Endereços">
    {{-- 
        LÓGICA ALPINE.JS 
        - Controla o formulário, máscaras e chamadas de API (ViaCEP e IBGE)
    --}}
    <div x-data="{ 
        showForm: false,
        loadingAddress: false,
        loadingCities: false,
        
        // Dados do Formulário (Vinculados via x-model)
        form: {
            zip_code: '',
            street: '',
            number: '',
            complement: '',
            neighborhood: '',
            city: '',
            state: ''
        },
        
        // Lista de cidades do estado selecionado
        cities: [],

        // --- MÁSCARAS ---
        formatCEP(value) {
            return value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2').substring(0, 9);
        },
        onlyNumbers(value) {
            return value.replace(/\D/g, '');
        },

        // --- BUSCA DE ENDEREÇO (ViaCEP) ---
        async fetchAddress() {
            // Remove formatação para verificar tamanho (8 dígitos)
            const cleanCep = this.form.zip_code.replace(/\D/g, '');
            
            if (cleanCep.length === 8) {
                this.loadingAddress = true;
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
                    const data = await response.json();

                    if (!data.erro) {
                        this.form.street = data.logradouro;
                        this.form.neighborhood = data.bairro;
                        this.form.complement = data.complemento;
                        this.form.state = data.uf;
                        
                        // Após definir o estado, carregamos as cidades e depois selecionamos a cidade correta
                        await this.fetchCities(); 
                        this.form.city = data.localidade;
                        
                        // Foca no número, que é o que falta preencher
                        this.$nextTick(() => document.getElementById('numberInput').focus());
                    } else {
                        alert('CEP não encontrado.');
                    }
                } catch (error) {
                    console.error('Erro ao buscar CEP:', error);
                } finally {
                    this.loadingAddress = false;
                }
            }
        },

        // --- BUSCA DE CIDADES (IBGE) ---
        async fetchCities() {
            if (!this.form.state) return;
            
            this.loadingCities = true;
            this.cities = []; // Limpa lista atual

            try {
                const response = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${this.form.state}/municipios`);
                const data = await response.json();
                // Ordena por nome
                this.cities = data.sort((a, b) => a.nome.localeCompare(b.nome));
            } catch (error) {
                console.error('Erro ao carregar cidades:', error);
            } finally {
                this.loadingCities = false;
            }
        }
    }" 
    {{-- Ao iniciar, se já houver erro de validação (old), recarrega as cidades do estado selecionado --}}
    x-init="$watch('form.state', value => fetchCities())"
    >
        
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Endereços</h2>
            
            <button @click="showForm = !showForm" 
                    class="h-10 px-6 border border-black rounded-xl text-sm font-bold transition-all duration-200"
                    :class="showForm ? 'bg-white text-black' : 'bg-black text-white hover:bg-white hover:text-black'">
                <span x-text="showForm ? 'CANCELAR' : '+ NOVO ENDEREÇO'"></span>
            </button>
        </div>

        {{-- Formulário de Cadastro --}}
        <div x-show="showForm" x-transition.opacity class="bg-white p-6 rounded-xl mb-10 border border-gray-200 shadow-sm">
            <h3 class="font-bold text-lg mb-6 text-gray-900 flex items-center gap-2">
                Novo Endereço
                {{-- Spinner de carregamento do CEP --}}
                <svg x-show="loadingAddress" class="animate-spin h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </h3>
            
            <form action="{{ route('profile.address.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                    
                    {{-- CEP --}}
                    <div class="md:col-span-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">CEP</label>
                        <input type="text" name="zip_code" 
                               x-model="form.zip_code"
                               @input="form.zip_code = formatCEP($el.value); fetchAddress()"
                               maxlength="9"
                               placeholder="00000-000"
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Rua --}}
                    <div class="md:col-span-8">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Rua / Avenida</label>
                        <input type="text" name="street" x-model="form.street"
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-gray-50 text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Número --}}
                    <div class="md:col-span-3">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Número</label>
                        <input type="text" name="number" id="numberInput"
                               x-model="form.number"
                               @input="form.number = onlyNumbers($el.value)"
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Complemento --}}
                    <div class="md:col-span-5">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Complemento</label>
                        <input type="text" name="complement" x-model="form.complement"
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Bairro --}}
                    <div class="md:col-span-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Bairro</label>
                        <input type="text" name="neighborhood" x-model="form.neighborhood"
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-gray-50 text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Estado (UF) --}}
                    <div class="md:col-span-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Estado</label>
                        <select name="state" x-model="form.state" @change="fetchCities()"
                                class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all uppercase appearance-none">
                            <option value="" disabled selected>Selecione...</option>
                            <option value="AC">Acre</option>
                            <option value="AL">Alagoas</option>
                            <option value="AP">Amapá</option>
                            <option value="AM">Amazonas</option>
                            <option value="BA">Bahia</option>
                            <option value="CE">Ceará</option>
                            <option value="DF">Distrito Federal</option>
                            <option value="ES">Espírito Santo</option>
                            <option value="GO">Goiás</option>
                            <option value="MA">Maranhão</option>
                            <option value="MT">Mato Grosso</option>
                            <option value="MS">Mato Grosso do Sul</option>
                            <option value="MG">Minas Gerais</option>
                            <option value="PA">Pará</option>
                            <option value="PB">Paraíba</option>
                            <option value="PR">Paraná</option>
                            <option value="PE">Pernambuco</option>
                            <option value="PI">Piauí</option>
                            <option value="RJ">Rio de Janeiro</option>
                            <option value="RN">Rio Grande do Norte</option>
                            <option value="RS">Rio Grande do Sul</option>
                            <option value="RO">Rondônia</option>
                            <option value="RR">Roraima</option>
                            <option value="SC">Santa Catarina</option>
                            <option value="SP">São Paulo</option>
                            <option value="SE">Sergipe</option>
                            <option value="TO">Tocantins</option>
                        </select>
                    </div>

                    {{-- Cidade (Agora é um Select dinâmico) --}}
                    <div class="md:col-span-8 relative">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Cidade</label>
                        
                        {{-- Select de Cidades --}}
                        <select name="city" x-model="form.city" :disabled="!form.state || loadingCities"
                                class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all appearance-none disabled:bg-gray-100 disabled:text-gray-400">
                            <option value="" disabled selected>Selecione a cidade...</option>
                            <template x-for="cityOption in cities" :key="cityOption.id">
                                <option :value="cityOption.nome" x-text="cityOption.nome"></option>
                            </template>
                        </select>

                        {{-- Spinner pequeno para carregar cidades --}}
                        <div x-show="loadingCities" class="absolute right-4 top-10">
                            <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                    </div>

                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="h-12 px-8 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer shadow-lg">
                        SALVAR ENDEREÇO
                    </button>
                </div>
            </form>
        </div>

        {{-- Lista de Endereços --}}
        @if($addresses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($addresses as $address)
                    <div class="border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-shadow relative bg-white group">
                        <div class="pr-8">
                            <p class="font-black text-lg text-gray-900 mb-1">{{ $address->street }}, {{ $address->number }}</p>
                            <p class="text-sm text-gray-600">{{ $address->complement }}</p>
                            <p class="text-sm text-gray-600">{{ $address->neighborhood }}</p>
                            <p class="text-sm text-gray-600 font-medium mt-2">{{ $address->city }} - {{ $address->state }}</p>
                            <p class="text-xs text-gray-400 mt-1 font-mono tracking-wide">{{ $address->zip_code }}</p>
                        </div>
                        
                        <form action="{{ route('profile.address.delete', $address->id) }}" method="POST" class="absolute top-6 right-6" onsubmit="return confirm('Tem certeza que deseja remover este endereço?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-300 hover:text-red-600 transition-colors p-1" title="Excluir">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                <p class="text-gray-500 italic">Você ainda não cadastrou nenhum endereço.</p>
            </div>
        @endif
    </div>
</x-profile.layout>