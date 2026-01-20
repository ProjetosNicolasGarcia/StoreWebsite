<x-layout>
    {{-- 
        BLOCO DE PREPARAÇÃO DE DADOS (PHP) 
    --}}
    {{-- 
        BLOCO DE PREPARAÇÃO DE DADOS (PHP) 
    --}}
    @php
        // 1. Preparação das Imagens do PAI (Produto Principal)
        $parentImages = [];
        if ($product->image_url) {
            $parentImages[] = asset('storage/' . $product->image_url);
        }
        if ($product->gallery && is_array($product->gallery)) {
            foreach ($product->gallery as $img) {
                $parentImages[] = asset('storage/' . $img);
            }
        }

        // 2. Descobre a COR da variante padrão para associar às imagens do pai
        $defaultColor = null;
        $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
        
        if ($defaultVariant && $defaultVariant->options) {
            foreach(['Cor', 'color', 'COR', 'Color', 'cor'] as $tryKey) {
                if (isset($defaultVariant->options[$tryKey])) {
                    $defaultColor = $defaultVariant->options[$tryKey];
                    break;
                }
            }
        }

        // 3. Inicializa o mapa de backup JÁ com as imagens do pai na cor padrão
        $colorImagesFallback = [];
        if ($defaultColor && !empty($parentImages)) {
            $colorImagesFallback[$defaultColor] = $parentImages;
        }

        // 4. Agrupa opções e processa outras variantes
        $optionsMap = [];
        
        if ($product->variants) {
            foreach ($product->variants as $variant) {
                if ($variant->options) {
                    foreach ($variant->options as $key => $value) {
                        $optionsMap[$key][] = $value;
                    }

                    // --- LÓGICA DE DETECÇÃO DE COR ---
                    $colorKey = null;
                    foreach(['Cor', 'color', 'COR', 'Color', 'cor'] as $tryKey) {
                        if (isset($variant->options[$tryKey])) {
                            $colorKey = $tryKey;
                            break;
                        }
                    }

                    // Se essa variante TEM imagens próprias, ela sobrescreve ou adiciona ao mapa
                    // Isso permite que você tenha "Branco" (Pai) e "Preto" (Variante com fotos próprias)
                    if ($colorKey) {
                        $colorName = $variant->options[$colorKey];
                        
                        $imgs = [];
                        if ($variant->image) $imgs[] = asset('storage/' . $variant->image);
                        if ($variant->images && is_array($variant->images)) {
                            foreach($variant->images as $img) $imgs[] = asset('storage/' . $img);
                        }

                        // Se achou imagens ESPECÍFICAS nesta variante, atualiza o mapa
                        if (count($imgs) > 0) {
                            $colorImagesFallback[$colorName] = $imgs;
                        }
                    }
                }
            }
        }
        
        foreach ($optionsMap as $key => $values) {
            $optionsMap[$key] = array_unique($values);
        }

        // 5. Prepara o JSON para o Front-end
        $jsVariants = $product->variants->map(function($v) use ($colorImagesFallback, $parentImages, $defaultColor) {
            $isOnSale = $v->isOnSale(); 
            $discountPercent = ($isOnSale && $v->price > 0) 
                ? round((($v->price - $v->sale_price) / $v->price) * 100) 
                : 0;

            // --- MONTAGEM DA GALERIA ---
            $variantImages = [];
            
            // A. Imagens Próprias da Variante
            if ($v->image) $variantImages[] = asset('storage/' . $v->image);
            if ($v->images && is_array($v->images)) {
                foreach($v->images as $img) $variantImages[] = asset('storage/' . $img);
            }

            // B. Se vazia, tenta pegar do Mapa de Cores (que já contem o Pai/Padrão)
            if (empty($variantImages)) {
                $colorVal = null;
                foreach(['Cor', 'color', 'COR', 'Color', 'cor'] as $tryKey) {
                    if (isset($v->options[$tryKey])) {
                        $colorVal = $v->options[$tryKey];
                        break;
                    }
                }
                if ($colorVal && isset($colorImagesFallback[$colorVal])) {
                    $variantImages = $colorImagesFallback[$colorVal];
                }
            }
            
            // C. Último recurso: Se ainda estiver vazio, usa as imagens do Pai
            if (empty($variantImages)) {
                $variantImages = $parentImages;
            }

            return [
                'id' => $v->id,
                'price' => (float) $v->price,
                'sale_price' => $isOnSale ? (float) $v->sale_price : null,
                'is_on_sale' => $isOnSale,
                'sale_end_date' => ($isOnSale && $v->sale_end_date) ? $v->sale_end_date->format('Y-m-d H:i:s') : null,
                'discount_percentage' => $discountPercent,
                'options' => $v->options,
                'stock' => $v->quantity,
                'sku' => $v->sku,
                'gallery' => $variantImages
            ];
        });

        // 6. Base Images para o JS (Imagens do Pai)
        $jsBaseImages = $parentImages;
    @endphp

    {{-- INÍCIO DO COMPONENTE ALPINE PRINCIPAL --}}
    <div class="container mx-auto px-4 py-8 pt-24 md:pt-32"
         x-data="productSelector({ 
             variants: {{ json_encode($jsVariants) }}, 
             baseImages: {{ json_encode($jsBaseImages) }},
             colorImages: {{ json_encode($colorImagesFallback) }} 
         })">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
            
            {{-- Coluna 1: Carrossel --}}
            <div class="space-y-4">
                <div class="relative w-full overflow-hidden rounded-2xl border border-gray-100 group bg-gray-50">
                    <div class="relative h-[500px] w-full flex items-center justify-center">
                        <img :src="currentImage" 
                             class="absolute inset-0 w-full h-full object-contain transition-all duration-300"
                             alt="{{ $product->name }}">
                    </div>

                    {{-- Setas --}}
                    <button x-show="galleryImages.length > 1" @click="prevImage()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-black p-3 rounded-full shadow-lg backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100 translate-x-[-10px] group-hover:translate-x-0 z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button x-show="galleryImages.length > 1" @click="nextImage()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-black p-3 rounded-full shadow-lg backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100 translate-x-[10px] group-hover:translate-x-0 z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                    
                    {{-- Contador --}}
                    <div x-show="galleryImages.length > 1" class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/70 px-4 py-1.5 rounded-full text-white text-xs font-bold backdrop-blur-md tracking-widest z-10">
                        <span x-text="galleryImages.indexOf(currentImage) + 1"></span> / <span x-text="galleryImages.length"></span>
                    </div>
                </div>

                {{-- Miniaturas --}}
                <div class="flex flex-wrap gap-2" x-show="galleryImages.length > 1">
                    <template x-for="(image, index) in galleryImages" :key="index">
                        <button @click="currentImage = image"
                            class="w-16 h-16 rounded-lg overflow-hidden border border-transparent bg-gray-50 transition-all duration-200 flex-shrink-0"
                            :class="currentImage === image ? 'border-black ring-1 ring-black' : 'hover:border-gray-300 opacity-70 hover:opacity-100'">
                            <img :src="image" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Coluna 2: Info e Seleção --}}
            <div class="flex flex-col space-y-8">
                <div>
                    @if($product->category)
                        <p class="text-sm text-gray-500 uppercase tracking-widest mb-2">{{ $product->category->name }}</p>
                    @endif
                    <h1 class="text-4xl font-black text-black uppercase tracking-tight leading-none">{{ $product->name }}</h1>
                    
                    {{-- Reviews --}}
                    <div class="flex items-center mt-4 space-x-2">
                        <div class="flex text-yellow-500">
                            @for($i=0; $i<5; $i++) <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg> @endfor
                        </div>
                        <span class="text-sm text-gray-500">({{ $product->reviews_count ?? 0 }} avaliações)</span>
                    </div>

                    {{-- Preço --}}
                    <div class="mt-6">
                        <template x-if="!selectedVariant">
                             <div>
                                <p class="text-sm text-gray-500 mb-1">A partir de</p>
                                <p class="text-3xl font-bold text-gray-900">R$ {{ number_format($product->price, 2, ',', '.') }}</p>
                             </div>
                        </template>

                        <template x-if="selectedVariant">
                            <div>
                                <template x-if="selectedVariant.is_on_sale">
                                    <div class="flex flex-col items-start">
                                        <div class="flex items-end gap-3">
                                            <span class="text-4xl font-black text-red-600" x-text="formatMoney(selectedVariant.sale_price)"></span>
                                            <span class="text-xl text-gray-400 line-through mb-1" x-text="formatMoney(selectedVariant.price)"></span>
                                            <span class="mb-2 bg-red-100 text-red-800 text-sm font-bold px-2.5 py-0.5 rounded">
                                                <span x-text="selectedVariant.discount_percentage"></span>% OFF
                                            </span>
                                        </div>
                                        <template x-if="selectedVariant.sale_end_date">
                                            <div class="mt-4 w-full p-4 bg-red-50 border border-red-100 rounded-lg">
                                                <p class="text-red-800 text-xs font-bold uppercase tracking-widest mb-1">A oferta termina em:</p>
                                                <div class="flex gap-4 text-red-700 font-mono text-xl font-bold" x-text="timerDisplay">Calculando...</div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!selectedVariant.is_on_sale">
                                    <p class="text-3xl font-bold text-gray-900" x-text="formatMoney(selectedVariant.price)"></p>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Seletores --}}
                <div class="space-y-4">
                    @foreach($optionsMap as $optionName => $optionValues)
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-2">{{ $optionName }}</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($optionValues as $value)
                                    <button 
                                        @click="selectOption('{{ $optionName }}', '{{ $value }}')"
                                        class="px-4 py-2 border rounded-lg transition-all text-sm font-medium"
                                        :class="selectedOptions['{{ $optionName }}'] === '{{ $value }}' 
                                            ? 'border-black bg-black text-white' 
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-400'"
                                    >
                                        {{ $value }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Botão de Compra --}}
                <div class="flex flex-col gap-4">
                    <form action="{{ route('cart.add', $product->id) }}" method="POST" class="w-full" @submit.prevent="submitCart($el)">
                        @csrf
                        <input type="hidden" name="variant_id" :value="selectedVariant ? selectedVariant.id : ''">
                        
                        <button type="submit" 
                                :disabled="!canAddToCart"
                                :class="!canAddToCart ? 'bg-gray-200 cursor-not-allowed text-gray-400 border-gray-200' : 'bg-black hover:bg-white hover:text-black text-white border-black'"
                                class="w-full flex justify-center items-center h-12 px-4 border rounded-xl shadow-sm text-base font-bold transition-all duration-200 focus:outline-none uppercase tracking-widest">
                            <span x-text="getButtonLabel()"></span>
                        </button>
                    </form>
                    
                    <p x-show="!isSelectionComplete && Object.keys(optionsMap).length > 0" class="text-xs text-red-500 text-center font-bold animate-pulse">
                        Por favor, selecione {{ implode(' e ', array_keys($optionsMap)) }} para continuar.
                    </p>
                </div>

                {{-- Frete --}}
                <div class="py-6"
                    x-data="{ 
                        zipCode: '', 
                        loading: false, 
                        result: null, 
                        error: null,
                        async calculate() {
                            const cleanCep = this.zipCode.replace(/\D/g, '');
                            if (cleanCep.length !== 8) { this.error = 'CEP Inválido'; return; }
                            this.loading = true; this.error = null; this.result = null;
                            try {
                                const response = await axios.post('{{ route('shipping.calculate') }}', { zip_code: cleanCep, product_id: {{ $product->id }} });
                                this.result = response.data;
                            } catch (e) { this.error = 'Erro ao calcular.'; } finally { this.loading = false; }
                        }
                    }">
                    <label class="block text-xs font-bold text-gray-900 uppercase tracking-widest mb-3">Calcular Frete</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="zipCode" @keydown.enter.prevent="calculate()" @input="$el.value = $el.value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2')" placeholder="00000-000" class="block flex-1 h-12 px-4 rounded-xl border border-gray-500 bg-white text-gray-900 focus:border-black focus:ring-black">
                        <button @click="calculate()" :disabled="loading" class="h-12 px-6 border border-gray-500 rounded-xl bg-gray-100 text-gray-900 font-bold uppercase hover:bg-white hover:border-black transition-all disabled:opacity-50">
                            <span x-show="!loading">OK</span><span x-show="loading">...</span>
                        </button>
                    </div>
                    <div x-show="error" style="display: none;" class="mt-3 text-xs text-red-600 font-bold"><span x-text="error"></span></div>
                    <div x-show="result" style="display: none;" class="mt-4 space-y-2">
                        <template x-for="option in result" :key="option.name">
                            <div class="flex justify-between items-center text-sm pb-2">
                                <div><span class="font-bold text-gray-900 uppercase" x-text="option.name"></span><br><span class="text-gray-500 text-xs">Até <span x-text="option.days"></span> dias úteis</span></div>
                                <div class="font-bold text-gray-900">R$ <span x-text="new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(option.price)"></span></div>
                            </div>
                        </template>
                    </div>
                </div>
                
                {{-- Compartilhar --}}
                <div class="flex items-center gap-4" x-data="{ copied: false }">
                    <span class="text-sm font-bold text-gray-900 uppercase tracking-widest">Compartilhar:</span>
                    <button @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000);" class="group flex items-center justify-center h-12 w-12 rounded-xl border border-gray-500 hover:bg-white hover:border-black transition bg-white shadow-sm">
                        <svg x-show="!copied" class="w-6 h-6 text-gray-500 group-hover:text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                        <svg x-show="copied" style="display: none;" class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Detalhes, etc. (Mantidos) --}}
        <div class="border-t border-gray-200 mt-16 max-w-4xl mx-auto">
            <div x-data="{ open: true }" class="border-b border-gray-200">
                <button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group"><span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Descrição</span><span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span></button>
                <div x-show="open" class="pb-8 prose text-gray-600 max-w-none">{!! $product->description !!}</div>
            </div>
             <div x-data="{ open: false }" class="border-b border-gray-200">
                <button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group"><span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Características</span><span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span></button>
                <div x-show="open" x-transition class="pb-8 text-gray-600">
                    @if(!empty($product->characteristics) && is_array($product->characteristics))
                        <dl>@foreach($product->characteristics as $key => $value) <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"><dt class="text-sm font-bold text-gray-900">{{ $key }}</dt><dd class="mt-1 text-sm text-gray-700 sm:col-span-2 sm:mt-0">{{ $value }}</dd></div> @endforeach</dl>
                    @else <p class="italic text-gray-400">Nenhuma informação técnica disponível.</p> @endif
                </div>
            </div>
             <div x-data="{ open: false }" class="border-b border-gray-200">
                <button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group"><span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Avaliações</span><span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span></button>
                <div x-show="open" x-transition class="pb-8 text-gray-600">
                    @if($product->reviews && $product->reviews->count() > 0)
                        @foreach($product->reviews as $review) <div class="mb-6 border-b border-gray-100 pb-4 last:border-0"><div class="flex items-center justify-between mb-2"><p class="font-bold text-gray-900">{{ $review->user->name ?? 'Cliente' }}</p><span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</span></div><div class="flex text-yellow-500 mb-2">@for($i=0; $i<$review->rating; $i++) ★ @endfor</div><p class="text-sm leading-relaxed">{{ $review->content }}</p></div> @endforeach
                    @else <p class="italic text-gray-500">Ainda não há avaliações.</p> @endif
                </div>
            </div>
        </div>

        {{-- Relacionados --}}
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
                                <p class="text-gray-500">R$ {{ number_format($related->price, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- SCRIPT ALPINE.JS --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productSelector', (config) => ({
                variants: config.variants,
                optionsMap: @json($optionsMap),
                selectedOptions: {},
                selectedVariant: null,
                colorImages: config.colorImages, // Recebe o mapa de imagens por cor
                
                currentImage: config.baseImages[0],
                galleryImages: config.baseImages,
                
                timerDisplay: '',
                timerInterval: null,

                init() {},

                selectOption(key, value) {
                    this.selectedOptions[key] = value;

                    // --- 1. TROCA IMEDIATA DE IMAGEM (Baseada na Cor) ---
                    // Se o valor clicado (ex: "Preto") existe no mapa de imagens, troca a galeria agora.
                    if (this.colorImages[value]) {
                        this.galleryImages = this.colorImages[value];
                        this.currentImage = this.galleryImages[0];
                    }
                    // -----------------------------------------------------

                    this.findMatchingVariant();
                },

         findMatchingVariant() {
            const match = this.variants.find(variant => {
                // CORREÇÃO: Tratamos opções nulas como objeto vazio, em vez de retornar false
                const variantOptions = variant.options || {}; 
                
                // Verifica se as opções selecionadas batem com as da variante
                for (const [key, value] of Object.entries(this.selectedOptions)) {
                    if (variantOptions[key] !== value) return false;
                }
                
                return true;
            });

            const totalRequiredOptions = Object.keys(this.optionsMap).length;
            const totalSelectedOptions = Object.keys(this.selectedOptions).length;

            // Se encontrou match E o número de opções selecionadas bate com o necessário
            // (Para produto único, ambos serão 0, então 0 === 0 é verdadeiro)
            if (match && totalRequiredOptions === totalSelectedOptions) {
                this.selectedVariant = match;
                
                // --- LÓGICA DE TROCA DE GALERIA ---
                if (match.gallery && match.gallery.length > 0) {
                    this.galleryImages = match.gallery;
                    this.currentImage = match.gallery[0];
                } 
                // ----------------------------------

                this.stopTimer();
                if (match.is_on_sale && match.sale_end_date) {
                    this.startTimer(match.sale_end_date);
                }
            } else {
                this.selectedVariant = null;
                this.stopTimer();
            }
        },

                startTimer(expiryDateString) {
                    const expiry = new Date(expiryDateString).getTime();
                    this.updateTimerDisplay(expiry);
                    this.timerInterval = setInterval(() => { this.updateTimerDisplay(expiry); }, 1000);
                },
                stopTimer() { if (this.timerInterval) { clearInterval(this.timerInterval); this.timerInterval = null; this.timerDisplay = ''; } },
                updateTimerDisplay(expiry) {
                    const now = new Date().getTime();
                    const distance = expiry - now;
                    if (distance < 0) { this.timerDisplay = "EXPIRADO"; this.stopTimer(); return; }
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    this.timerDisplay = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                },
                get isSelectionComplete() { return Object.keys(this.optionsMap).length === Object.keys(this.selectedOptions).length; },
                get canAddToCart() { return this.isSelectionComplete && this.selectedVariant && this.selectedVariant.stock > 0; },
                getButtonLabel() {
                    if (!this.isSelectionComplete) return 'SELECIONE AS OPÇÕES';
                    if (!this.selectedVariant) return 'INDISPONÍVEL';
                    if (this.selectedVariant.stock <= 0) return 'ESGOTADO';
                    return 'COMPRAR AGORA';
                },
                formatMoney(value) { return 'R$ ' + new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(value); },
                nextImage() {
                    const currentIndex = this.galleryImages.indexOf(this.currentImage);
                    const nextIndex = (currentIndex + 1) % this.galleryImages.length;
                    this.currentImage = this.galleryImages[nextIndex];
                },
                prevImage() {
                    const currentIndex = this.galleryImages.indexOf(this.currentImage);
                    const prevIndex = (currentIndex === 0) ? this.galleryImages.length - 1 : currentIndex - 1;
                    this.currentImage = this.galleryImages[prevIndex];
                },
                setMainImage(index) { this.currentImage = this.galleryImages[index]; },
                submitCart(form) { if (this.canAddToCart) form.submit(); }
            }));
        });
    </script>
</x-layout>