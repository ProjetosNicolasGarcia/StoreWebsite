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
              // [CORREÇÃO] Simplificado com null-safe operator para evitar erro de sintaxe
              originalBirthDate: '{{ $user->birth_date?->format("Y-m-d") }}',
              
              // --- ESTADO ATUAL (Reativo via x-model) ---
              currentEmail: '{{ old('email', $user->email) }}',
              currentPhone: '{{ old('phone', $user->phone) }}',
              currentCPF: '{{ old('cpf', $user->cpf) }}',
              // [CORREÇÃO] Simplificado com null-safe operator e aspas duplas no formato
              currentBirthDate: '{{ old('birth_date', $user->birth_date?->format("Y-m-d")) }}',
              
              newPassword: '',
              
              // --- LÓGICA DE DETECÇÃO DE ALTERAÇÕES ---
              // Retorna true se qualquer dado sensível for diferente do original
              isSensitiveChange() {
                  return (this.currentEmail !== this.originalEmail) || 
                         (this.currentPhone !== this.originalPhone) || 
                         (this.currentCPF !== this.originalCPF) || 
                         (this.currentBirthDate !== this.originalBirthDate) ||
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
          {{-- Campo: Nome (Dado não sensível) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                       class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: Sobrenome (Dado não sensível) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Sobrenome</label>
                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" 
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

            {{-- Campo: Data de Nascimento --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Data de Nascimento</label>
                <input type="date" name="birth_date" x-model="currentBirthDate"
                       max="{{ date('Y-m-d', strtotime('-18 years')) }}"
                       class="block w-full h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                @error('birth_date')
                    <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                @enderror
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
                        Para alterar dados sensíveis (E-mail, CPF, Telefone, Data de Nascimento ou Senha), precisamos confirmar que é você.
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
                    class="h-12 px-8 border border-black rounded-xl text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer shadow-lg uppercase">
                Salvar Alterações
            </button>
        </div>
    </form>

    {{-- 
        =================================================================
        ZONA DE PERIGO / EXCLUSÃO DE CONTA
        =================================================================
    --}}
    <div class="mt-16 pt-10 border-t border-gray-200">
        <h3 class="text-lg font-bold text-red-600 uppercase tracking-wide">Excluir Conta</h3>
        <p class="mt-2 text-sm text-gray-600 max-w-2xl">
            Uma vez que sua conta for excluída, você perderá acesso ao histórico de pedidos e seus dados de login. 
            Esta ação desativa seu acesso imediatamente.
        </p>

        {{-- Lógica Alpine para o Modal de Exclusão --}}
        <div class="mt-10 flex justify-end" x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
            
            <button @click="open = true" type="button" 
                    class="h-12 px-8 border border-red-600 rounded-xl text-base font-bold text-white bg-red-600 hover:bg-white hover:text-red-600 uppercase transition-all duration-200 cursor-pointer shadow-lg">
                Excluir minha conta
            </button>

            {{-- Modal Wrapper --}}
            <div x-show="open" style="display: none;" 
                 class="fixed inset-0 z-50 overflow-y-auto" 
                 aria-labelledby="modal-title" role="dialog" aria-modal="true">
                
                {{-- Backdrop com desfoque (Blur) e fundo escuro transparente --}}
                <div x-show="open" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity"></div>

                {{-- Centralização Mobile (items-center) --}}
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    
                    <div x-show="open" 
                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         @click.away="open = false"
                         class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        
                        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                            @csrf
                            @method('delete')

                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                    <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Confirmar Exclusão de Conta</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Tem certeza que deseja excluir sua conta? Todos os seus dados de acesso serão removidos permanentemente. 
                                            Para confirmar, digite sua senha abaixo.
                                        </p>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <input type="password" name="password" placeholder="Sua senha atual"
                                               class="block w-full h-11 px-4 rounded-lg border border-gray-300 text-gray-900 placeholder:text-gray-400 focus:border-red-500 focus:ring-red-500 sm:text-sm">
                                        
                                        @if($errors->userDeletion->has('password'))
                                            <p class="mt-2 text-sm text-red-600 font-bold">
                                                {{ $errors->userDeletion->first('password') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-row-reverse gap-2">
                                {{-- Botão Confirmar --}}
                                <button type="submit" class="inline-flex w-full justify-center rounded-xl border border-red-600 bg-red-600 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-white hover:text-red-600 transition-all duration-200 sm:w-auto uppercase tracking-wide">
                                    Confirmar Exclusão
                                </button>
                                
                                {{-- Botão Cancelar --}}
                                <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-900 shadow-sm hover:bg-gray-900 hover:text-white hover:border-gray-900 transition-all duration-200 sm:mt-0 sm:w-auto uppercase tracking-wide">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-profile.layout>