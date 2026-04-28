<x-layout>
    {{-- Define o título da página no slot de head --}}
    @section('title', 'Fale Conosco')

    {{-- Estrutura Principal: Fundo cinza claro e espaçamento superior para o Header fixo --}}
    <div class="bg-gray-50 min-h-screen pt-40 pb-24 px-4">
        <div class="container mx-auto max-w-5xl">
            
            {{-- Cabeçalho da Seção: Título e subtexto centralizados --}}
            <div class="text-center mb-16">
                <h1 id="contact-heading" class="text-4xl md:text-5xl font-black text-gray-900 uppercase tracking-tighter mb-4">
                    Fale Conosco
                </h1>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                    Estamos aqui para ajudar. Preencha o formulário abaixo e nossa equipe entrará em contato.
                </p>
            </div>

            {{-- Grid de Conteúdo: Divide informações de contato e formulário --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                {{-- Coluna da Esquerda: Informações de Atendimento (Cartão Preto) --}}
                <div class="bg-black text-white p-10 rounded-2xl shadow-xl flex flex-col justify-between h-full" aria-label="Canais de Atendimento e Informações">
                    <div>
                        <h3 class="text-2xl font-bold uppercase tracking-widest mb-8">Canais</h3>
                        
                        <div class="space-y-6">
                            {{-- Informação de E-mail --}}
                            <div class="flex items-start space-x-4">
                                <svg aria-hidden="true" class="w-6 h-6 mt-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <p class="font-bold text-sm text-gray-400 uppercase">E-mail</p>
                                    <p class="text-lg">suporte@minhaloja.com.br</p>
                                </div>
                            </div>
                            
                            {{-- Informação de Horário de Funcionamento --}}
                            <div class="flex items-start space-x-4">
                                <svg aria-hidden="true" class="w-6 h-6 mt-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="font-bold text-sm text-gray-400 uppercase">Horário</p>
                                    <p class="text-lg">Seg. a Sex. das 9h às 18h</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Rodapé da Coluna de Informações --}}
                    <div class="mt-12">
                        <p class="text-gray-400 text-sm">Acompanhe nossas redes sociais.</p>
                    </div>
                </div>

                {{-- Coluna da Direita: Formulário de Contato --}}
                <div class="md:col-span-2 bg-white p-8 md:p-12 rounded-2xl shadow-xl border border-gray-100" aria-labelledby="contact-heading">
                    
                    <div aria-live="polite">
                        {{-- Feedback Visual: Mensagem de Sucesso após envio --}}
                        @if(session('success'))
                            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-r" role="status">
                                <div class="flex">
                                    <div class="py-1">
                                        <svg aria-hidden="true" class="fill-current h-6 w-6 text-green-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-bold">Mensagem enviada!</p>
                                        <p class="text-sm">{{ session('success') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Início do Formulário --}}
                    <form action="{{ route('pages.contact.send') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Campo: Nome --}}
                            <div class="flex flex-col">
                                <label for="name" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nome Completo</label>
                                <input type="text" name="name" id="name" required aria-required="true" value="{{ old('name') }}"
                                       aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                       aria-describedby="{{ $errors->has('name') ? 'name-error' : '' }}"
                                       class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4" placeholder="Ex: João Silva">
                                @error('name') <span id="name-error" role="alert" class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- Campo: E-mail --}}
                            <div class="flex flex-col">
                                <label for="email" class="text-xs font-bold text-gray-700 uppercase tracking-widest mb-2">E-mail</label>
                                <input type="email" name="email" id="email" required aria-required="true" value="{{ old('email') }}"
                                       aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                       aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                                       class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4" placeholder="Ex: joao@email.com">
                                @error('email') <span id="email-error" role="alert" class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Linha 2: Assunto e Número do Pedido --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Campo: Assunto --}}
                            <div class="flex flex-col">
                                <label for="subject" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Assunto</label>
                                <select name="subject" id="subject" required aria-required="true"
                                        aria-invalid="{{ $errors->has('subject') ? 'true' : 'false' }}"
                                        aria-describedby="{{ $errors->has('subject') ? 'subject-error' : '' }}"
                                        class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4 cursor-pointer">
                                    <option value="" disabled selected>Selecione...</option>
                                    <option value="Dúvida sobre Produto" {{ old('subject') == 'Dúvida sobre Produto' ? 'selected' : '' }}>Dúvida sobre Produto</option>
                                    <option value="Status do Pedido" {{ old('subject') == 'Status do Pedido' ? 'selected' : '' }}>Status do Pedido</option>
                                    <option value="Troca ou Devolução" {{ old('subject') == 'Troca ou Devolução' ? 'selected' : '' }}>Troca ou Devolução</option>
                                    <option value="Outros" {{ old('subject') == 'Outros' ? 'selected' : '' }}>Outros</option>
                                </select>
                                @error('subject') <span id="subject-error" role="alert" class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- Campo: Número do Pedido --}}
                            <div class="flex flex-col">
                                <label for="order_number" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Pedido (Opcional)</label>
                                <input type="text" name="order_number" id="order_number" value="{{ old('order_number') }}"
                                       class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4" placeholder="#12345">
                            </div>
                        </div>

                        {{-- Linha 3: Campo de Mensagem --}}
                        <div class="flex flex-col">
                            <label for="message" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Mensagem</label>
                            <textarea name="message" id="message" rows="6" required aria-required="true"
                                      aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}"
                                      aria-describedby="{{ $errors->has('message') ? 'message-error' : '' }}"
                                      class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4 resize-y" placeholder="Como podemos ajudar?">{{ old('message') }}</textarea>
                            @error('message') <span id="message-error" role="alert" class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Botão de Submissão --}}
                        <button type="submit" aria-label="Enviar sua mensagem" class="w-full bg-black hover:bg-gray-800 text-white font-bold py-4 px-8 rounded-lg uppercase tracking-widest transition-all shadow-lg text-sm focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 cursor-pointer">
                            Enviar Mensagem
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>