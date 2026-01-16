<x-layout>
    <div class="container mx-auto px-4 py-8 pt-24 md:pt-32">
        
        {{-- Seção Principal: Grid de 2 Colunas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
            
            {{-- Coluna 1: Carrossel de Imagens --}}
            <div x-data="{ 
                activeImage: '{{ is_array($product->images) ? ($product->images[0] ?? '') : $product->image_url }}',
                images: {{ is_array($product->images) ? json_encode($product->images) : json_encode([$product->image_url]) }}
            }" class="space-y-4">
                
                {{-- Imagem Principal (Fundo Branco) --}}
                <div class="aspect-[3/4] md:aspect-square bg-white rounded-lg overflow-hidden relative group border border-gray-100">
                    <img :src="'/storage/' + activeImage" alt="{{ $product->name }}" class="w-full h-full object-contain p-4">
                </div>

                {{-- Miniaturas --}}
                @if(is_array($product->images) && count($product->images) > 1)
                    <div class="grid grid-cols-4 gap-4">
                        <template x-for="image in images">
                            <button 
                                @click="activeImage = image"
                                class="aspect-square rounded-md overflow-hidden border-2 bg-white"
                                :class="activeImage === image ? 'border-black' : 'border-transparent hover:border-gray-300'"
                            >
                                <img :src="'/storage/' + image" class="w-full h-full object-contain p-1">
                            </button>
                        </template>
                    </div>
                @endif
            </div>

            {{-- Coluna 2: Informações e Ações --}}
            <div class="flex flex-col space-y-8">
                
                {{-- Cabeçalho do Produto --}}
                <div>
                    @if($product->category)
                        <p class="text-sm text-gray-500 uppercase tracking-widest mb-2">{{ $product->category->name }}</p>
                    @endif
                    <h1 class="text-4xl font-black text-black uppercase tracking-tight leading-none">{{ $product->name }}</h1>
                    
                    <div class="flex items-center mt-4 space-x-2">
                        {{-- Estrelas --}}
                        <div class="flex text-yellow-500">
                            @for($i=0; $i<5; $i++)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <span class="text-sm text-gray-500">({{ $product->reviews_count ?? 0 }} avaliações)</span>
                    </div>

                    {{-- Preço --}}
                    <div class="mt-6">
                        @if($product->isOnSale())
                            <div class="flex flex-col items-start">
                                <div class="flex items-end gap-3">
                                    <span class="text-4xl font-black text-red-600">
                                        R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                                    </span>
                                    <span class="text-xl text-gray-400 line-through mb-1">
                                        R$ {{ number_format($product->base_price, 2, ',', '.') }}
                                    </span>
                                    <span class="mb-2 bg-red-100 text-red-800 text-sm font-bold px-2.5 py-0.5 rounded">
                                        {{ $product->discount_percentage }}% OFF
                                    </span>
                                </div>
                                @if($product->sale_end_date)
                                    <div class="mt-4 w-full p-4 bg-red-50 border border-red-100 rounded-lg" x-data="countdown('{{ $product->sale_end_date->format('Y-m-d H:i:s') }}')">
                                        <p class="text-red-800 text-xs font-bold uppercase tracking-widest mb-1">A oferta termina em:</p>
                                        <div class="flex gap-4 text-red-700 font-mono text-xl font-bold" x-text="timerDisplay">Carregando...</div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-3xl font-bold text-gray-900">
                                R$ {{ number_format($product->base_price, 2, ',', '.') }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Botão ÚNICO de Ação --}}
                <div class="flex flex-col gap-4">
                    <form action="{{ route('cart.add', $product->id) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" 
                                class="w-full flex justify-center items-center h-12 px-4 border border-black rounded-xl shadow-sm text-base font-bold text-white bg-black hover:bg-white hover:text-black transition-all duration-200 cursor-pointer focus:outline-none uppercase tracking-widest">
                            Comprar Agora
                        </button>
                    </form>
                </div>

                {{-- Calculadora de Frete --}}
                {{-- ALTERADO AQUI: Removido 'border-t border-b border-gray-200' --}}
                <div class="py-6"
                     x-data="{ 
                         zipCode: '', 
                         loading: false, 
                         result: null, 
                         error: null,
                         async calculate() {
                             const cleanCep = this.zipCode.replace(/\D/g, '');
                             if (cleanCep.length !== 8) {
                                 this.error = 'Digite um CEP válido.';
                                 this.result = null; return;
                             }
                             this.loading = true; this.error = null; this.result = null;
                             try {
                                 const response = await axios.post('{{ route('shipping.calculate') }}', { zip_code: cleanCep, product_id: {{ $product->id }} });
                                 this.result = response.data;
                             } catch (e) { this.error = 'Erro ao calcular.'; console.error(e); } finally { this.loading = false; }
                         }
                     }">
                    
                    <label class="block text-xs font-bold text-gray-900 uppercase tracking-widest mb-3">Calcular Frete</label>
                    <div class="flex gap-2">
                        <input type="text" 
                               x-model="zipCode" 
                               @keydown.enter.prevent="calculate()"
                               @input="$el.value = $el.value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2')"
                               placeholder="00000-000" maxlength="9"
                               class="block flex-1 h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 shadow-none focus:border-black focus:ring-black transition-all">
                        
                        <button @click="calculate()" :disabled="loading"
                                class="h-12 px-6 border border-gray-500 rounded-xl bg-gray-100 text-gray-900 font-bold uppercase hover:bg-white hover:border-black transition-all disabled:opacity-50">
                            <span x-show="!loading">OK</span>
                            <span x-show="loading">...</span>
                        </button>
                    </div>

                    <div x-show="error" style="display: none;" class="mt-3 text-xs text-red-600 font-bold flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                        <span x-text="error"></span>
                    </div>

                    <div x-show="result" style="display: none;" class="mt-4 space-y-2">
                        <template x-for="option in result" :key="option.name">
                            <div class="flex justify-between items-center text-sm pb-2">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 uppercase" x-text="option.name"></span>
                                    <span class="text-gray-500 text-xs">Até <span x-text="option.days"></span> dias úteis</span>
                                </div>
                                <div class="font-bold text-gray-900">R$ <span x-text="new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(option.price)"></span></div>
                            </div>
                        </template>
                    </div>
                    <a href="https://buscacepinter.correios.com.br/app/endereco/index.php" target="_blank" class="text-xs text-gray-500 mt-2 inline-block hover:underline">Não sei meu CEP</a>
                </div>

                {{-- Botão de Compartilhar --}}
                <div class="flex items-center gap-4" x-data="{ copied: false }">
                    <span class="text-sm font-bold text-gray-900 uppercase tracking-widest">Compartilhar:</span>
                    <button 
                        @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000);"
                        class="group flex items-center justify-center h-12 w-12 rounded-xl border border-gray-500 hover:bg-white hover:border-black transition duration-300 bg-white shadow-sm"
                        title="Copiar link">
                        <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-500 group-hover:text-black transition">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                        </svg>
                        <svg x-show="copied" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600 transition">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Seção de Detalhes --}}
        <div class="border-t border-gray-200 mt-16 max-w-4xl mx-auto">
            <div x-data="{ open: true }" class="border-b border-gray-200">
                <button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group">
                    <span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Descrição</span>
                    <span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span>
                </button>
                <div x-show="open" x-transition class="pb-8 prose text-gray-600 max-w-none">{!! $product->description !!}</div>
            </div>

            <div x-data="{ open: false }" class="border-b border-gray-200">
                <button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group">
                    <span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Características</span>
                    <span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span>
                </button>
                <div x-show="open" x-transition class="pb-8 text-gray-600">
                    @if(!empty($product->characteristics) && is_array($product->characteristics))
                        <dl>
                            @foreach($product->characteristics as $key => $value)
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                    <dt class="text-sm font-bold text-gray-900">{{ $key }}</dt>
                                    <dd class="mt-1 text-sm text-gray-700 sm:col-span-2 sm:mt-0">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <p class="italic text-gray-400">Nenhuma informação técnica disponível.</p>
                    @endif
                </div>
            </div>

            <div x-data="{ open: false }" class="border-b border-gray-200">
                <button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group">
                    <span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Avaliações</span>
                    <span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span>
                </button>
                <div x-show="open" x-transition class="pb-8 text-gray-600">
                    @if($product->reviews && $product->reviews->count() > 0)
                        @foreach($product->reviews as $review)
                            <div class="mb-6 border-b border-gray-100 pb-4 last:border-0">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="font-bold text-gray-900">{{ $review->user->name ?? 'Cliente' }}</p>
                                    <span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex text-yellow-500 mb-2">@for($i=0; $i<$review->rating; $i++) ★ @endfor</div>
                                <p class="text-sm leading-relaxed">{{ $review->content }}</p>
                            </div>
                        @endforeach
                    @else
                        <p class="italic text-gray-500">Ainda não há avaliações.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Produtos Relacionados --}}
        <div class="mt-24">
            <h2 class="text-2xl font-black uppercase tracking-widest mb-10 text-center">Você também pode gostar</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach($relatedProducts as $related)
                    <a href="{{ route('shop.product', $related->slug) }}" class="block group cursor-pointer">
                        <div class="aspect-[3/4] bg-gray-50 rounded-lg overflow-hidden mb-4 relative flex items-center justify-center">
                             <img src="{{ Storage::url($related->image_url) }}" class="w-full h-full object-contain p-4 group-hover:scale-110 transition duration-500">
                        </div>
                        <div class="text-center">
                            <h3 class="font-bold text-gray-900 group-hover:text-gray-600 transition">{{ $related->name }}</h3>
                            <div class="mt-1 flex justify-center items-center gap-2">
                                @if($related->isOnSale())
                                    <span class="text-xs text-gray-400 line-through">R$ {{ number_format($related->base_price, 2, ',', '.') }}</span>
                                    <span class="font-bold text-gray-900">R$ {{ number_format($related->sale_price, 2, ',', '.') }}</span>
                                @else
                                    <p class="text-gray-500">R$ {{ number_format($related->base_price, 2, ',', '.') }}</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Script do Contador --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('countdown', (expiryDate) => ({
                timerDisplay: 'Carregando...',
                expiry: new Date(expiryDate).getTime(),
                interval: null,
                init() { this.updateTimer(); this.interval = setInterval(() => { this.updateTimer(); }, 1000); },
                updateTimer() {
                    const now = new Date().getTime(); const distance = this.expiry - now;
                    if (distance < 0) { this.timerDisplay = "OFERTA EXPIRADA"; clearInterval(this.interval); return; }
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    this.timerDisplay = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                }
            }))
        })
    </script>
</x-layout>