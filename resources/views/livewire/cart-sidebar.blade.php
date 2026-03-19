<div x-cloak x-show="cartOpen" 
     x-transition:enter="transition ease-in-out duration-300 transform" 
     x-transition:enter-start="translate-x-full" 
     x-transition:enter-end="translate-x-0" 
     x-transition:leave="transition ease-in-out duration-300 transform" 
     x-transition:leave-start="translate-x-0" 
     x-transition:leave-end="translate-x-full" 
     class="fixed inset-y-0 right-0 w-full md:w-[450px] bg-white z-[70] shadow-2xl flex flex-col h-full">
    
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
        <h2 class="text-xl font-black uppercase tracking-tight">Seu Carrinho</h2>
        {{-- [UX FIX] Adicionado cursor-pointer e hover:scale-110 --}}
        <button @click="cartOpen = false" class="text-gray-400 hover:text-black transition-all duration-200 cursor-pointer hover:scale-110 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>

    {{-- Área dos produtos do carrinho --}}
    <div class="flex-1 overflow-y-auto p-6 space-y-6 relative">
        
        {{-- OVERLAY DE LOADING --}}
        <div wire:loading.flex class="absolute inset-0 z-50 bg-white/70 backdrop-blur-sm flex-col items-center justify-center">
            <svg class="animate-spin h-10 w-10 text-black mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-bold text-gray-600 uppercase tracking-widest animate-pulse">Atualizando...</span>
        </div>

        @if(isset($cartItems) && $cartItems->count() > 0)
            @foreach($cartItems as $item)
                @if(!$item->variant && !$item->product) @continue @endif

                <div class="flex gap-4" wire:key="cart-item-{{ $item->id }}">
                    @php
                        $img = $item->product->image_url; 
                        if ($item->variant) {
                            if ($item->variant->image) {
                                $img = $item->variant->image;
                            } elseif (!empty($item->variant->images) && isset($item->variant->images[0])) {
                                $img = $item->variant->images[0];
                            } 
                            else {
                                $colorOptions = ['Cor', 'Color', 'COR', 'cor', 'color'];
                                $variantColor = null;
                                if (is_array($item->variant->options)) {
                                    foreach ($colorOptions as $key) {
                                        if (isset($item->variant->options[$key])) {
                                            $variantColor = $item->variant->options[$key];
                                            break;
                                        }
                                    }
                                }
                                if ($variantColor && $item->product->variants) {
                                    foreach ($item->product->variants as $sibling) {
                                        if ($sibling->id === $item->variant->id) continue;
                                        $siblingColor = null;
                                        if ($sibling->options) {
                                            foreach ($colorOptions as $key) {
                                                if (isset($sibling->options[$key])) {
                                                    $siblingColor = $sibling->options[$key];
                                                    break;
                                                }
                                            }
                                        }
                                        if ($siblingColor === $variantColor) {
                                            if ($sibling->image) {
                                                $img = $sibling->image;
                                                break;
                                            } elseif (!empty($sibling->images) && isset($sibling->images[0])) {
                                                $img = $sibling->images[0];
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    @endphp
                    
                    <div class="w-20 h-24 bg-white rounded-md overflow-hidden flex-shrink-0 border border-gray-200 flex items-center justify-center">
                        <img src="{{ Storage::url($img) }}" loading="lazy" decoding="async" alt="{{ $item->product->name }}" class="w-full h-full object-contain p-1">
                    </div>

                    <div class="flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                {{-- [UX FIX] Adicionado cursor-pointer e hover:underline --}}
                                <h3 class="font-bold text-sm uppercase tracking-wide text-gray-900 line-clamp-2">
                                    <a href="{{ route('shop.product', $item->product->slug) }}" class="cursor-pointer hover:underline underline-offset-2">{{ $item->product->name }}</a>
                                </h3>
                                
                                {{-- [UX FIX] Adicionado cursor-pointer e hover:scale-110 --}}
                                <button type="button" 
                                        wire:click="removeItem({{ $item->id }})" 
                                        wire:loading.attr="disabled"
                                        class="text-gray-400 hover:text-red-600 transition-all duration-200 cursor-pointer hover:scale-110 disabled:opacity-30 disabled:cursor-not-allowed focus:outline-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456-1.21L9.264 9m9.968-3.21c-3.185-.81-7.156-.81-10.34 0m9.68 0H14.16M13.68 3.5c-.32-.61-.925-1.02-1.63-1.02H11.95c-.705 0-1.31.41-1.63 1.02M10.34 4.79H9.264" /></svg>
                                </button>
                            </div>

                            <div class="mt-1">
                                @if($item->product->categories && $item->product->categories->isNotEmpty())
                                    <span class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">
                                        {{ $item->product->categories->first()->name }}
                                    </span>
                                @endif

                                @if($item->variant && is_array($item->variant->options))
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($item->variant->options as $key => $value)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded text-gray-600 uppercase font-bold">
                                                {{ $key }}: {{ $value }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-end justify-between mt-2">
                            <div class="flex items-center border border-gray-200 rounded h-8">
                                
                                {{-- [UX FIX] Adicionado cursor-pointer --}}
                                <button type="button" 
                                        wire:click="updateQuantity({{ $item->id }}, 'decrease')" 
                                        wire:loading.attr="disabled"
                                        class="w-8 h-full flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-black transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed disabled:bg-gray-50 focus:outline-none">
                                    -
                                </button>
                                
                                <span class="w-8 h-full flex items-center justify-center text-xs font-bold">{{ $item->quantity }}</span>
                                
                                {{-- [UX FIX] Adicionado cursor-pointer --}}
                                <button type="button" 
                                        wire:click="updateQuantity({{ $item->id }}, 'increase')" 
                                        wire:loading.attr="disabled"
                                        class="w-8 h-full flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-black transition cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed disabled:bg-gray-50 focus:outline-none">
                                    +
                                </button>

                            </div>

                            <div class="text-right">
                                @php
                                    $unitPrice = $item->variant ? $item->variant->final_price : ($item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price);
                                    $isOnSale = $item->variant ? $item->variant->isOnSale() : $item->product->isOnSale();
                                    $originalPrice = $item->variant ? $item->variant->price : $item->product->base_price;
                                @endphp

                                @if($isOnSale)
                                    <p class="text-xs text-gray-400 line-through">R$ {{ number_format($originalPrice * $item->quantity, 2, ',', '.') }}</p>
                                    <p class="font-bold text-red-600">R$ {{ number_format($unitPrice * $item->quantity, 2, ',', '.') }}</p>
                                @else
                                    <p class="font-bold text-gray-900">R$ {{ number_format($unitPrice * $item->quantity, 2, ',', '.') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="h-full flex flex-col items-center justify-center text-center space-y-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-16 h-16 text-gray-300"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                <p class="text-gray-500">Seu carrinho está vazio.</p>
                {{-- [UX FIX] Adicionado cursor-pointer --}}
                <button @click="cartOpen = false" class="text-black font-bold border-b border-black hover:text-gray-600 hover:border-gray-600 transition cursor-pointer focus:outline-none">Continuar comprando</button>
            </div>
        @endif
    </div>

    @if(isset($cartItems) && $cartItems->count() > 0)
        <div class="p-6 border-t border-gray-100 relative">
            
            <div wire:loading class="absolute inset-0 z-10 bg-white/50 backdrop-blur-sm cursor-wait"></div>

            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-500 uppercase text-xs tracking-widest">Subtotal</span>
                <span class="font-black text-xl">R$ {{ number_format($cartTotal ?? 0, 2, ',', '.') }}</span>
            </div>
            @auth
                {{-- [UX FIX] Adicionado cursor-pointer --}}
                <a href="{{ route('checkout') }}" class="block w-full text-center bg-black text-white border border-black rounded-xl py-4 font-bold uppercase tracking-widest hover:bg-white hover:text-black transition duration-300 cursor-pointer">
                    Finalizar Compra
                </a>
            @else
                {{-- [UX FIX] Adicionado cursor-pointer --}}
                <button type="button" @click="cartOpen = false; setTimeout(() => $dispatch('open-auth-slider'), 300)" class="w-full bg-black text-white border border-black rounded-xl py-4 font-bold uppercase tracking-widest hover:bg-white hover:text-black transition duration-300 cursor-pointer focus:outline-none">
                    Finalizar Compra
                </button>
            @endauth
        </div>
    @endif
</div>