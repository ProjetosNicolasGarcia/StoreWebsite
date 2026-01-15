<x-layout>
    @section('title', 'Fale Conosco')

    <div class="bg-gray-50 min-h-screen pt-40 pb-24 px-4">
        <div class="container mx-auto max-w-5xl">
            
            {{-- Cabeçalho da Seção --}}
            <div class="text-center mb-16">
                <h1 class="text-4xl md:text-5xl font-black text-gray-900 uppercase tracking-tighter mb-4">
                    Fale Conosco
                </h1>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                    Estamos aqui para ajudar. Preencha o formulário abaixo e nossa equipe entrará em contato.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                {{-- Coluna da Esquerda: Informações --}}
                <div class="bg-black text-white p-10 rounded-2xl shadow-xl flex flex-col justify-between h-full">
                    <div>
                        <h3 class="text-2xl font-bold uppercase tracking-widest mb-8">Canais</h3>
                        
                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <svg class="w-6 h-6 mt-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <div>
                                    <p class="font-bold text-sm text-gray-400 uppercase">E-mail</p>
                                    <p class="text-lg">suporte@minhaloja.com.br</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-4">
                                <svg class="w-6 h-6 mt-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div>
                                    <p class="font-bold text-sm text-gray-400 uppercase">Horário</p>
                                    <p class="text-lg">Seg. a Sex. das 9h às 18h</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-12">
                        <p class="text-gray-400 text-sm">Acompanhe nossas redes sociais.</p>
                    </div>
                </div>

                {{-- Coluna da Direita: O Formulário --}}
                <div class="md:col-span-2 bg-white p-8 md:p-12 rounded-2xl shadow-xl border border-gray-100">
                    
                    {{-- Bloco de Sucesso: Verifique se este @endif está presente --}}
                    @if(session('success'))
                        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-r" role="alert">
                            <div class="flex">
                                <div class="py-1"><svg class="fill-current h-6 w-6 text-green-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM6.7 9.29L9 11.6l4.3-4.3 1.4 1.42L9 14.4l-3.7-3.7 1.4-1.42z"/></svg></div>
                                <div>
                                    <p class="font-bold">Mensagem enviada!</p>
                                    <p class="text-sm">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    {{-- Fim do bloco de sucesso --}}

                    <form action="{{ route('pages.contact.send') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col">
                                <label for="name" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Nome Completo</label>
                                <input type="text" name="name" id="name" required value="{{ old('name') }}"
                                       class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4" placeholder="Ex: João Silva">
                                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex flex-col">
                                <label for="email" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">E-mail</label>
                                <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                       class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4" placeholder="Ex: joao@email.com">
                                @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col">
                                <label for="subject" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Assunto</label>
                                <select name="subject" id="subject" required
                                        class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4">
                                    <option value="" disabled selected>Selecione...</option>
                                    <option value="Dúvida sobre Produto">Dúvida sobre Produto</option>
                                    <option value="Status do Pedido">Status do Pedido</option>
                                    <option value="Troca ou Devolução">Troca ou Devolução</option>
                                    <option value="Outros">Outros</option>
                                </select>
                                @error('subject') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex flex-col">
                                <label for="order_number" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Pedido (Opcional)</label>
                                <input type="text" name="order_number" id="order_number" value="{{ old('order_number') }}"
                                       class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4" placeholder="#12345">
                            </div>
                        </div>

                        <div class="flex flex-col">
                            <label for="message" class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Mensagem</label>
                            <textarea name="message" id="message" rows="6" required
                                      class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-full p-4 resize-y" placeholder="Como podemos ajudar?">{{ old('message') }}</textarea>
                            @error('message') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full bg-black hover:bg-gray-800 text-white font-bold py-4 px-8 rounded-lg uppercase tracking-widest transition-all shadow-lg text-sm">
                            Enviar Mensagem
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>