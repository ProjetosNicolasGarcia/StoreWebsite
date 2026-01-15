<x-profile.layout title="Meus Endereços">
    <div x-data="{ 
        showForm: false,
        formatCEP(value) {
            return value.replace(/\D/g, '')
                        .replace(/^(\d{5})(\d)/, '$1-$2')
                        .substring(0, 9);
        },
        onlyNumbers(value) {
            return value.replace(/\D/g, '');
        }
    }">
        
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Endereços</h2>
            
            <button @click="showForm = !showForm" 
                    class="h-10 px-6 border border-black rounded-xl text-sm font-bold transition-all duration-200"
                    :class="showForm ? 'bg-white text-black' : 'bg-black text-white hover:bg-white hover:text-black'">
                <span x-text="showForm ? 'CANCELAR' : '+ NOVO ENDEREÇO'"></span>
            </button>
        </div>

        {{-- Formulário de Cadastro --}}
        <div x-show="showForm" x-transition.opacity class="bg-white p-6 rounded-xl mb-10 border border-gray-200">
            <h3 class="font-bold text-lg mb-6 text-gray-900">Novo Endereço</h3>
            
            <form action="{{ route('profile.address.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    
                    {{-- CEP --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">CEP</label>
                        <input type="text" name="zip_code" 
                               x-on:input="$el.value = formatCEP($el.value)"
                               maxlength="9"
                               placeholder="00000-000"
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Rua --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Rua / Avenida</label>
                        <input type="text" name="street" 
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Número --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Número</label>
                        <input type="text" name="number" 
                               x-on:input="$el.value = onlyNumbers($el.value)"
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Complemento --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Complemento</label>
                        <input type="text" name="complement" 
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Bairro (CORRIGIDO: name="neighborhood") --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Bairro</label>
                        <input type="text" name="neighborhood" 
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- Cidade --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Cidade</label>
                        <input type="text" name="city" 
                               class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    </div>

                    {{-- UF --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">UF</label>
                        <select name="state" 
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
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="h-12 px-6 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer">
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
                            {{-- CORRIGIDO: Exibindo neighborhood em vez de district --}}
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