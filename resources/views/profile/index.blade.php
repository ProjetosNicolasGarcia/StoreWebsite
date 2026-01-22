<x-profile.layout title="Meus Dados">
    {{-- Cabeçalho da Seção --}}
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Meus Dados</h2>
    </div>

    {{-- 
        Formulário de Perfil com Lógica Alpine.js 
        Monitora se campos críticos foram alterados para exigir a senha atual do usuário.
    --}}
    <form action="{{ route('profile.update') }}" method="POST" 
          x-data="{ 
              // --- ESTADO INICIAL (Snapshot do Banco de Dados) ---
              originalEmail: '{{ $user->email }}',
              originalPhone: '{{ $user->phone }}',
              originalCPF: '{{ $user->cpf }}',
              
              // --- ESTADO ATUAL (Reativo via x-model) ---
              currentEmail: '{{ old('email', $user->email) }}',
              currentPhone: '{{ old('phone', $user->phone) }}',
              currentCPF: '{{ old('cpf', $user->cpf) }}',
              newPassword: '',
              
              // --- LÓGICA DE DETECÇÃO DE ALTERAÇÕES ---
              // Retorna true se qualquer dado sensível for diferente do original
              isSensitiveChange() {
                  return (this.currentEmail !== this.originalEmail) || 
                         (this.currentPhone !== this.originalPhone) || 
                         (this.currentCPF !== this.originalCPF) || 
                         (this.newPassword.length > 0);
              },

              // --- MÁSCARAS DE INPUT ---
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
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Campo: Nome Completo (Dado não sensível) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome Completo</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                       class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: E-mail (Sensível) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">E-mail</label>
                <input type="email" name="email" x-model="currentEmail"
                       class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: CPF (Sensível com Máscara) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">CPF</label>
                <input type="text" name="cpf" x-model="currentCPF"
                       x-on:input="currentCPF = formatCPF($el.value)"
                       maxlength="14" placeholder="000.000.000-00"
                       class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: Telefone (Sensível com Máscara) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Telefone / Celular</label>
                <input type="text" name="phone" x-model="currentPhone"
                       x-on:input="currentPhone = formatPhone($el.value)"
                       maxlength="15" placeholder="(00) 00000-0000"
                       class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>
        </div>

        {{-- Seção: Alteração de Senha --}}
        <div class="mt-10 border-t border-gray-200 pt-8">
            <h3 class="text-lg font-bold mb-6 text-gray-900 uppercase tracking-wide">Alterar Senha</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nova Senha</label>
                    <input type="password" name="password" x-model="newPassword"
                           class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    <p class="text-xs text-gray-500 mt-1">Deixe em branco para manter a atual.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Confirmar Senha</label>
                    <input type="password" name="password_confirmation" 
                           class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                </div>
            </div>
        </div>

        {{-- BOX DE SEGURANÇA: Aparece apenas se houver mudanças sensíveis ou erro de validação --}}
        <div x-show="isSensitiveChange() || {{ $errors->has('current_password') ? 'true' : 'false' }}" 
             x-transition.opacity
             class="mt-8 bg-yellow-50 border border-yellow-200 p-6 rounded-xl">
            
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-yellow-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-bold text-yellow-800 uppercase tracking-wide mb-2">Confirmação de Segurança</h4>
                    <p class="text-sm text-yellow-700 mb-4">
                        Para alterar dados sensíveis (E-mail, CPF, Telefone ou Senha), precisamos confirmar que é você.
                    </p>
                    
                    <label class="block text-sm font-bold text-gray-700 mb-1">Digite sua Senha Atual</label>
                    <input type="password" name="current_password" 
                           class="block w-full md:w-1/2 h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    
                    @error('current_password')
                        <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Botão de Submissão --}}
        <div class="mt-10 flex justify-end">
            <button type="submit" 
                    class="h-12 px-8 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer shadow-lg">
                SALVAR ALTERAÇÕES
            </button>
        </div>
    </form>
</x-profile.layout>