<x-profile.layout title="Meus Dados">
    {{-- Cabeçalho da Seção --}}
    <div class="flex justify-between items-center mb-8">
        <h2 id="profile-heading" class="text-2xl font-black text-gray-900 uppercase tracking-tight">Meus Dados</h2>
    </div>

    {{-- 
        Formulário de Perfil com Lógica Alpine.js 
        Monitora se campos críticos foram alterados para exigir a senha atual do usuário.
    --}}
    <form action="{{ route('profile.update') }}" method="POST" aria-labelledby="profile-heading"
          x-data="{ 
              // --- ESTADO INICIAL (Snapshot do Banco de Dados) ---
              originalEmail: '{{ $user->email }}',
              originalPhone: '{{ $user->phone }}',
              originalCPF: '{{ $user->cpf }}',
              originalBirthDate: '{{ $user->birth_date?->format('Y-m-d') }}',
              
              // --- ESTADO ATUAL (Reativo via x-model) ---
              currentEmail: '{{ old('email', $user->email) }}',
              currentPhone: '{{ old('phone', $user->phone) }}',
              currentCPF: '{{ old('cpf', $user->cpf) }}',
              currentBirthDate: '{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}',
              
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
                <label for="name" class="block text-sm font-bold text-gray-700 mb-1">Nome</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" 
                       aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                       class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: Sobrenome (Dado não sensível) --}}
            <div>
                <label for="last_name" class="block text-sm font-bold text-gray-700 mb-1">Sobrenome</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" 
                       aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}"
                       class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: E-mail (Sensível) --}}
            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 mb-1">E-mail</label>
                <input type="email" id="email" name="email" x-model="currentEmail"
                       aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                       class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: CPF (Sensível com Máscara) --}}
            <div>
                <label for="cpf" class="block text-sm font-bold text-gray-700 mb-1">CPF</label>
                <input type="text" id="cpf" name="cpf" x-model="currentCPF"
                       x-on:input="currentCPF = formatCPF($el.value)"
                       maxlength="14" placeholder="000.000.000-00"
                       aria-invalid="{{ $errors->has('cpf') ? 'true' : 'false' }}"
                       class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: Telefone (Sensível com Máscara) --}}
            <div>
                <label for="phone" class="block text-sm font-bold text-gray-700 mb-1">Telefone / Celular</label>
                <input type="text" id="phone" name="phone" x-model="currentPhone"
                       x-on:input="currentPhone = formatPhone($el.value)"
                       maxlength="15" placeholder="(00) 00000-0000"
                       aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                       class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
            </div>

            {{-- Campo: Data de Nascimento --}}
            <div>
                <label for="birth_date" class="block text-sm font-bold text-gray-700 mb-1">Data de Nascimento</label>
                <input type="date" id="birth_date" name="birth_date" x-model="currentBirthDate"
                       max="{{ date('Y-m-d', strtotime('-18 years')) }}"
                       aria-invalid="{{ $errors->has('birth_date') ? 'true' : 'false' }}"
                       aria-describedby="{{ $errors->has('birth_date') ? 'birth_date-error' : '' }}"
                       class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                @error('birth_date')
                    <p id="birth_date-error" class="text-red-500 text-xs mt-1 font-bold" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Seção: Alteração de Senha --}}
        <div class="mt-10 border-t border-gray-200 pt-8" role="group" aria-labelledby="password-heading">
            <h3 id="password-heading" class="text-lg font-bold mb-6 text-gray-900 uppercase tracking-wide">Alterar Senha</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-1">Nova Senha</label>
                    <input type="password" id="password" name="password" x-model="newPassword"
                           aria-describedby="password-help"
                           class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                    <p id="password-help" class="text-xs text-gray-500 mt-1">Deixe em branco para manter a atual.</p>
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-bold text-gray-700 mb-1">Confirmar Senha</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" 
                           class="block w-full h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                </div>
            </div>
        </div>

        {{-- BOX DE SEGURANÇA: Aparece apenas se houver mudanças sensíveis ou erro de validação --}}
        <div aria-live="polite">
            <div x-show="isSensitiveChange() || {{ $errors->has('current_password') ? 'true' : 'false' }}" 
                 x-transition.opacity
                 class="mt-8 bg-yellow-50 border border-yellow-200 p-6 rounded-none">
                
                <div class="flex items-start gap-3">
                    <svg aria-hidden="true" class="w-6 h-6 text-yellow-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <div class="flex-1">
                        <h4 id="security-check-heading" class="text-sm font-bold text-yellow-800 uppercase tracking-wide mb-2">Confirmação de Segurança</h4>
                        <p class="text-sm text-yellow-700 mb-4">
                            Para alterar dados sensíveis (E-mail, CPF, Telefone, Data de Nascimento ou Senha), precisamos confirmar que é você.
                        </p>
                        
                        <label for="current_password" class="block text-sm font-bold text-gray-700 mb-1">Digite sua Senha Atual</label>
                        <input type="password" id="current_password" name="current_password" 
                               aria-invalid="{{ $errors->has('current_password') ? 'true' : 'false' }}"
                               aria-describedby="{{ $errors->has('current_password') ? 'current_password-error' : '' }}"
                               class="block w-full md:w-1/2 h-12 px-4 rounded-none border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                        
                        @error('current_password')
                            <p id="current_password-error" class="text-red-500 text-xs mt-1 font-bold" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Botão de Submissão --}}
        <div class="mt-10 flex justify-end">
            <button type="submit" 
                    aria-label="Salvar as alterações de perfil"
                    class="h-12 px-8 border border-black rounded-none text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer shadow-none uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                Salvar Alterações
            </button>
        </div>
    </form>

    {{-- 
        =================================================================
        ZONA DE PERIGO / EXCLUSÃO DE CONTA
        =================================================================
    --}}
    <div class="mt-16 pt-10 border-t border-gray-200" aria-labelledby="danger-zone-heading">
        <h3 id="danger-zone-heading" class="text-lg font-bold text-red-600 uppercase tracking-wide">Excluir Conta</h3>
        <p class="mt-2 text-sm text-gray-600 max-w-2xl">
            Uma vez que sua conta for excluída, você perderá acesso ao histórico de pedidos e seus dados de login. 
            Esta ação desativa seu acesso imediatamente.
        </p>

        {{-- Lógica Alpine para o Modal de Exclusão --}}
        <div class="mt-10 flex justify-end" x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
            
            <button @click="open = true" type="button" 
                    aria-haspopup="dialog"
                    :aria-expanded="open.toString()"
                    aria-controls="delete-account-modal"
                    class="h-12 px-8 border border-red-600 rounded-none text-base font-bold text-white bg-red-600 hover:bg-white hover:text-red-600 uppercase tracking-widest transition-all duration-200 cursor-pointer shadow-none focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                Excluir minha conta
            </button>

            {{-- Modal Wrapper --}}
            <div id="delete-account-modal" x-show="open" style="display: none;" 
                 class="fixed inset-0 z-50 overflow-y-auto" 
                 aria-labelledby="modal-title" aria-describedby="modal-description" role="alertdialog" aria-modal="true">
                
                {{-- Backdrop com desfoque (Blur) e fundo escuro transparente --}}
                <div x-show="open" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

                {{-- Centralização Mobile (items-center) --}}
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    
                    <div x-show="open" 
                         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         @click.away="open = false"
                         class="relative transform overflow-hidden rounded-none bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200">
                        
                        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                            @csrf
                            @method('delete')

                            <div class="sm:flex sm:items-start">
                                {{-- Ícone de alerta quadrado --}}
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-none bg-red-100 sm:mx-0 sm:h-10 sm:w-10" aria-hidden="true">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                    <h3 class="text-base font-bold uppercase tracking-widest leading-6 text-gray-900" id="modal-title">Confirmar Exclusão de Conta</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500" id="modal-description">
                                            Tem certeza que deseja excluir sua conta? Todos os seus dados de acesso serão removidos permanentemente. 
                                            Para confirmar, digite sua senha abaixo.
                                        </p>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <label for="delete_password" class="sr-only">Senha para confirmar exclusão</label>
                                        <input type="password" id="delete_password" name="password" placeholder="Sua senha atual"
                                               aria-invalid="{{ $errors->userDeletion->has('password') ? 'true' : 'false' }}"
                                               aria-describedby="{{ $errors->userDeletion->has('password') ? 'delete-password-error' : '' }}"
                                               class="block w-full h-11 px-4 rounded-none border border-gray-500 text-gray-900 placeholder:text-gray-400 focus:border-red-500 focus:ring-red-500 sm:text-sm">
                                        
                                        @if($errors->userDeletion->has('password'))
                                            <p id="delete-password-error" class="mt-2 text-sm text-red-600 font-bold" role="alert">
                                                {{ $errors->userDeletion->first('password') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 sm:gap-2">
                                {{-- Botão Cancelar --}}
                                <button type="button" @click="open = false" 
                                        class="mt-3 sm:mt-0 inline-flex w-full justify-center rounded-none border border-gray-500 bg-white px-4 py-2 text-sm font-bold text-gray-900 hover:bg-black hover:text-white hover:border-black transition-all duration-200 sm:w-auto uppercase tracking-widest cursor-pointer focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2">
                                    Cancelar
                                </button>

                                {{-- Botão Confirmar --}}
                                <button type="submit" 
                                        aria-label="Confirmar exclusão permanente da conta"
                                        class="inline-flex w-full justify-center rounded-none border border-red-600 bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-white hover:text-red-600 transition-all duration-200 sm:w-auto uppercase tracking-widest cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                                    Confirmar Exclusão
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-profile.layout>