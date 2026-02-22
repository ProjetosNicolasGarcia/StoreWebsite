<div class="bg-gray-50 min-h-screen pt-32 pb-10"
     x-data="{
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
         formatCardNumber(value) {
             let v = value.replace(/\D/g, '');
             v = v.replace(/(\d{4})/g, '$1 ').trim();
             return v.substring(0, 19);
         },
         formatCardExpiry(value) {
             let v = value.replace(/\D/g, '');
             if (v.length >= 2) {
                 return v.substring(0, 2) + '/' + v.substring(2, 4);
             }
             return v;
         }
     }">
     
    <div class="container mx-auto px-4 max-w-7xl">
        <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900 mb-8">Finalizar Compra</h1>

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative mb-6">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <form wire:submit.prevent="placeOrder" 
              x-data="{ 
                  payMethod: '{{ $paymentMethod }}',
                  shipMethod: '{{ $shippingMethod }}',
                  addrId: {{ $selectedAddressId ?? 'null' }},
                  newAddr: {{ $useNewAddress ? 'true' : 'false' }}
              }" 
              class="flex flex-col lg:flex-row gap-8">
            
            {{-- COLUNA ESQUERDA: Formulários --}}
            <div class="order-2 lg:order-1 w-full lg:w-2/3 space-y-6">
                
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span> 
                        Identificação
                    </h2>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <p class="text-sm text-gray-600">Comprando como:</p>
                        <p class="font-bold text-gray-900">{{ Auth::user()->name }} <span class="text-gray-500 font-normal">({{ Auth::user()->email }})</span></p>
                    </div>
                </section>

                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span> 
                        Endereço de Entrega
                    </h2>
                    
                    @if(Auth::user()->addresses->isNotEmpty())
                        <div class="space-y-3 mb-4">
                            @foreach(Auth::user()->addresses as $addr)
                                <label :class="addrId === {{ $addr->id }} && !newAddr ? 'border-black bg-gray-50 ring-1 ring-black' : 'border-gray-200 hover:border-gray-300'" 
                                       class="flex items-start p-4 border rounded-xl cursor-pointer transition-colors">
                                    
                                    <input type="radio" name="address_group" value="{{ $addr->id }}" 
                                           @click="addrId = {{ $addr->id }}; newAddr = false; $wire.set('selectedAddressId', {{ $addr->id }}); $wire.set('useNewAddress', false)"
                                           :checked="addrId === {{ $addr->id }} && !newAddr"
                                           class="mt-1 text-black focus:ring-black cursor-pointer">
                                    
                                    <div class="ml-3">
                                        <p class="font-bold text-gray-900">{{ $addr->street }}, {{ $addr->number }}</p>
                                        <p class="text-sm text-gray-600">{{ $addr->neighborhood }} - {{ $addr->city }}/{{ $addr->state }}</p>
                                        <p class="text-xs text-gray-500 mt-1">CEP: {{ $addr->zip_code }}</p>
                                    </div>
                                </label>
                            @endforeach
                            
                            <label :class="newAddr ? 'border-black bg-gray-50 ring-1 ring-black' : 'border-gray-200 hover:border-gray-300'" 
                                   class="flex items-center p-4 border rounded-xl cursor-pointer transition-colors">
                                
                                <input type="radio" name="address_group" value="new" 
                                       @click="newAddr = true; addrId = null; $wire.set('useNewAddress', true); $wire.set('selectedAddressId', null)"
                                       :checked="newAddr"
                                       class="text-black focus:ring-black cursor-pointer">
                                
                                <span class="ml-3 font-bold text-gray-900">Entregar em outro endereço</span>
                            </label>
                        </div>
                    @endif

                    <div x-show="newAddr || {{ Auth::user()->addresses->isEmpty() ? 'true' : 'false' }}" x-transition style="display: none;" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-1">CEP</label>
                            <input type="text" wire:model="newAddress.zip_code" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" placeholder="00000-000">
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Rua / Avenida</label>
                            <input type="text" wire:model="newAddress.street" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Número</label>
                            <input type="text" wire:model="newAddress.number" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Complemento</label>
                            <input type="text" wire:model="newAddress.complement" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" placeholder="Apto, Bloco">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Bairro</label>
                            <input type="text" wire:model="newAddress.neighborhood" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Cidade</label>
                            <input type="text" wire:model="newAddress.city" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm">
                        </div>
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Estado (UF)</label>
                            <input type="text" wire:model="newAddress.state" maxlength="2" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm uppercase" placeholder="SP">
                        </div>
                    </div>
                </section>

                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">3</span> 
                        Opções de Frete
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label :class="shipMethod === 'pac' ? 'border-black bg-gray-50 ring-1 ring-black' : 'border-gray-200 hover:border-gray-300'" 
                               class="flex items-center p-4 border rounded-xl cursor-pointer transition-colors">
                            <input type="radio" name="shippingMethod" value="pac" 
                                   @click="shipMethod = 'pac'; $wire.set('shippingMethod', 'pac')" 
                                   :checked="shipMethod === 'pac'" 
                                   class="text-black focus:ring-black cursor-pointer">
                            <div class="ml-3 w-full flex justify-between">
                                <div>
                                    <p class="font-bold text-gray-900">Normal (PAC)</p>
                                    <p class="text-xs text-gray-500">7 a 10 dias úteis</p>
                                </div>
                                <span class="font-bold">R$ 15,00</span>
                            </div>
                        </label>
                        <label :class="shipMethod === 'sedex' ? 'border-black bg-gray-50 ring-1 ring-black' : 'border-gray-200 hover:border-gray-300'" 
                               class="flex items-center p-4 border rounded-xl cursor-pointer transition-colors">
                            <input type="radio" name="shippingMethod" value="sedex" 
                                   @click="shipMethod = 'sedex'; $wire.set('shippingMethod', 'sedex')" 
                                   :checked="shipMethod === 'sedex'" 
                                   class="text-black focus:ring-black cursor-pointer">
                            <div class="ml-3 w-full flex justify-between">
                                <div>
                                    <p class="font-bold text-gray-900">Expresso (Sedex)</p>
                                    <p class="text-xs text-gray-500">2 a 4 dias úteis</p>
                                </div>
                                <span class="font-bold">R$ 35,00</span>
                            </div>
                        </label>
                    </div>
                </section>

                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">4</span> 
                        Dados Pessoais
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nome Completo</label>
                            <input type="text" wire:model="fullName" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">CPF</label>
                            <input type="text" wire:model.defer="cpf" x-on:input="$el.value = formatCPF($el.value); $wire.set('cpf', $el.value)" maxlength="14" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" placeholder="000.000.000-00">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Telefone / WhatsApp</label>
                            <input type="text" wire:model.defer="phone" x-on:input="$el.value = formatPhone($el.value); $wire.set('phone', $el.value)" maxlength="15" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" placeholder="(11) 99999-9999">
                        </div>
                    </div>
                </section>

                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">5</span> 
                        Cupom de Desconto
                    </h2>
                    <div class="flex gap-2">
                        <input type="text" wire:model.defer="couponCode" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm uppercase" placeholder="Digite seu cupom">
                        
                        <button type="button" wire:click="applyCoupon" class="bg-black text-white border border-black px-6 rounded-xl font-bold hover:bg-white hover:text-black transition duration-300 text-sm whitespace-nowrap cursor-pointer">
                            Aplicar
                        </button>
                    </div>
                    @if (session()->has('coupon_success')) <p class="text-green-600 text-xs mt-2 font-bold">{{ session('coupon_success') }}</p> @endif
                </section>

                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">6</span> 
                        Forma de Pagamento
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <label :class="payMethod === 'credit_card' ? 'border-black bg-gray-50 ring-1 ring-black' : 'border-gray-200 hover:border-gray-300'" 
                               class="flex flex-col items-center justify-center p-4 border rounded-xl cursor-pointer transition-colors text-center h-24">
                            <input type="radio" name="paymentMethod" value="credit_card" 
                                   @click="payMethod = 'credit_card'; $wire.set('paymentMethod', 'credit_card')" 
                                   :checked="payMethod === 'credit_card'" class="sr-only">
                            <svg class="w-8 h-8 mb-2 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                            <span class="font-bold text-sm">Cartão de Crédito</span>
                        </label>
                        
                        <label :class="payMethod === 'pix' ? 'border-black bg-gray-50 ring-1 ring-black' : 'border-gray-200 hover:border-gray-300'" 
                               class="flex flex-col items-center justify-center p-4 border rounded-xl cursor-pointer transition-colors text-center h-24">
                            <input type="radio" name="paymentMethod" value="pix" 
                                   @click="payMethod = 'pix'; $wire.set('paymentMethod', 'pix')" 
                                   :checked="payMethod === 'pix'" class="sr-only">
                            
                            <svg class="w-8 h-8 mb-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor">
                                <path d="M306.4 356.5C311.8 351.1 321.1 351.1 326.5 356.5L403.5 433.5C417.7 447.7 436.6 455.5 456.6 455.5L471.7 455.5L374.6 552.6C344.3 582.1 295.1 582.1 264.8 552.6L167.3 455.2L176.6 455.2C196.6 455.2 215.5 447.4 229.7 433.2L306.4 356.5zM326.5 282.9C320.1 288.4 311.9 288.5 306.4 282.9L229.7 206.2C215.5 191.1 196.6 184.2 176.6 184.2L167.3 184.2L264.7 86.8C295.1 56.5 344.3 56.5 374.6 86.8L471.8 183.9L456.6 183.9C436.6 183.9 417.7 191.7 403.5 205.9L326.5 282.9zM176.6 206.7C190.4 206.7 203.1 212.3 213.7 222.1L290.4 298.8C297.6 305.1 307 309.6 316.5 309.6C325.9 309.6 335.3 305.1 342.5 298.8L419.5 221.8C429.3 212.1 442.8 206.5 456.6 206.5L494.3 206.5L552.6 264.8C582.9 295.1 582.9 344.3 552.6 374.6L494.3 432.9L456.6 432.9C442.8 432.9 429.3 427.3 419.5 417.5L342.5 340.5C328.6 326.6 304.3 326.6 290.4 340.6L213.7 417.2C203.1 427 190.4 432.6 176.6 432.6L144.8 432.6L86.8 374.6C56.5 344.3 56.5 295.1 86.8 264.8L144.8 206.7L176.6 206.7z"/>
                            </svg>
                            <span class="font-bold text-sm">PIX</span>
                        </label>
                        
                        <label :class="payMethod === 'boleto' ? 'border-black bg-gray-50 ring-1 ring-black' : 'border-gray-200 hover:border-gray-300'" 
                               class="flex flex-col items-center justify-center p-4 border rounded-xl cursor-pointer transition-colors text-center h-24">
                            <input type="radio" name="paymentMethod" value="boleto" 
                                   @click="payMethod = 'boleto'; $wire.set('paymentMethod', 'boleto')" 
                                   :checked="payMethod === 'boleto'" class="sr-only">
                            <svg class="w-8 h-8 mb-2 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4 6h2v12H4zm3 0h1v12H7zm2 0h3v12H9zm4 0h1v12h-1zm2 0h2v12h-2zm3 0h2v12h-2z"/>
                            </svg>
                            <span class="font-bold text-sm">Boleto</span>
                        </label>
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        
                        <div x-show="payMethod === 'credit_card'" x-transition style="display: none;" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Número do Cartão</label>
                                <div class="relative">
                                    <input type="text" x-on:input="$el.value = formatCardNumber($el.value)" maxlength="19" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" placeholder="0000 0000 0000 0000">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nome Impresso no Cartão</label>
                                <input type="text" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm uppercase" placeholder="JOÃO DA SILVA">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Validade</label>
                                <input type="text" x-on:input="$el.value = formatCardExpiry($el.value)" maxlength="5" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" placeholder="MM/AA">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">CVV</label>
                                <input type="text" maxlength="4" class="appearance-none rounded-xl block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-black focus:border-black sm:text-sm" placeholder="123">
                            </div>
                        </div>

                        <div x-show="payMethod === 'pix'" x-transition style="display: none;" class="mt-4 bg-gray-50 p-6 rounded-xl border border-gray-200 text-center flex flex-col items-center justify-center">
                            <div class="bg-white p-3 rounded-full shadow-sm mb-3">
                                <svg class="w-10 h-10 text-gray-900" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor">
                                    <path d="M306.4 356.5C311.8 351.1 321.1 351.1 326.5 356.5L403.5 433.5C417.7 447.7 436.6 455.5 456.6 455.5L471.7 455.5L374.6 552.6C344.3 582.1 295.1 582.1 264.8 552.6L167.3 455.2L176.6 455.2C196.6 455.2 215.5 447.4 229.7 433.2L306.4 356.5zM326.5 282.9C320.1 288.4 311.9 288.5 306.4 282.9L229.7 206.2C215.5 191.1 196.6 184.2 176.6 184.2L167.3 184.2L264.7 86.8C295.1 56.5 344.3 56.5 374.6 86.8L471.8 183.9L456.6 183.9C436.6 183.9 417.7 191.7 403.5 205.9L326.5 282.9zM176.6 206.7C190.4 206.7 203.1 212.3 213.7 222.1L290.4 298.8C297.6 305.1 307 309.6 316.5 309.6C325.9 309.6 335.3 305.1 342.5 298.8L419.5 221.8C429.3 212.1 442.8 206.5 456.6 206.5L494.3 206.5L552.6 264.8C582.9 295.1 582.9 344.3 552.6 374.6L494.3 432.9L456.6 432.9C442.8 432.9 429.3 427.3 419.5 417.5L342.5 340.5C328.6 326.6 304.3 326.6 290.4 340.6L213.7 417.2C203.1 427 190.4 432.6 176.6 432.6L144.8 432.6L86.8 374.6C56.5 344.3 56.5 295.1 86.8 264.8L144.8 206.7L176.6 206.7z"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900">Pagamento via PIX</h3>
                            <p class="text-sm text-gray-600 mt-2 max-w-sm">O código PIX Copia e Cola e o QR Code serão gerados na próxima tela, logo após você finalizar o pedido.</p>
                        </div>

                        <div x-show="payMethod === 'boleto'" x-transition style="display: none;" class="mt-4 bg-gray-50 p-6 rounded-xl border border-gray-200 text-center flex flex-col items-center justify-center">
                            <div class="bg-white p-3 rounded-full shadow-sm mb-3">
                                <svg class="w-10 h-10 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4 6h2v12H4zm3 0h1v12H7zm2 0h3v12H9zm4 0h1v12h-1zm2 0h2v12h-2zm3 0h2v12h-2z"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900">Pagamento via Boleto Bancário</h3>
                            <p class="text-sm text-gray-600 mt-2 max-w-sm">O boleto será gerado e enviado para o seu e-mail assim que o pedido for confirmado. A aprovação pode levar até 3 dias úteis.</p>
                        </div>

                    </div>
                </section>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:hidden mb-6" wire:ignore.self>
                    <h3 class="font-bold text-gray-900 mb-4 uppercase tracking-tight text-lg">Total da Compra</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-medium text-gray-900">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Frete</span>
                            <span class="font-medium text-gray-900">R$ {{ number_format($shippingPrice, 2, ',', '.') }}</span>
                        </div>
                        @if($discount > 0)
                            <div class="flex justify-between text-sm text-green-600">
                                <span>Desconto</span>
                                <span class="font-bold">- R$ {{ number_format($discount, 2, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100 mt-3">
                            <span class="font-bold text-lg text-gray-900">Total</span>
                            <span class="font-black text-2xl text-black">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-black text-white border border-black rounded-xl py-4 font-bold uppercase tracking-widest hover:bg-white hover:text-black hover:shadow-lg transition duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black cursor-pointer">
                    Finalizar Pedido
                </button>

            </div>

            {{-- COLUNA DIREITA: Resumo dos Produtos --}}
            <div class="order-1 lg:order-2 w-full lg:w-1/3" wire:ignore.self>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-24">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 uppercase tracking-tight">Resumo do Pedido</h2>
                    
                    {{-- A lista de produtos agora pode crescer sem gerar barra de rolagem (removido max-h e overflow) --}}
                    <div class="space-y-4 mb-6 pr-2">
                        @foreach($cartItems as $item)
                            <div class="flex gap-4">
                                <div class="w-16 h-20 bg-gray-50 rounded-md overflow-hidden flex-shrink-0 border border-gray-100 flex items-center justify-center">
                                    <img src="{{ Storage::url($item->product->image_url ?? '') }}" class="w-full h-full object-contain p-1">
                                </div>
                                <div class="flex-1 flex flex-col justify-between">
                                    <div>
                                        <p class="font-bold text-sm text-gray-900 line-clamp-2">{{ $item->product->name }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Qtd: {{ $item->quantity }}</p>
                                    </div>
                                    <p class="font-bold text-sm text-gray-900">R$ {{ number_format($item->total, 2, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-3 hidden lg:block">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-medium text-gray-900">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Frete</span>
                            <span class="font-medium text-gray-900">R$ {{ number_format($shippingPrice, 2, ',', '.') }}</span>
                        </div>
                        @if($discount > 0)
                            <div class="flex justify-between text-sm text-green-600">
                                <span>Desconto</span>
                                <span class="font-bold">- R$ {{ number_format($discount, 2, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100 mt-3">
                            <span class="font-bold text-lg text-gray-900">Total</span>
                            <span class="font-black text-2xl text-black">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>