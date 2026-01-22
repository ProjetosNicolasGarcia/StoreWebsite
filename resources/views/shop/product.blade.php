<x-layout>
    {{-- 
        =================================================================
        BLOCO PHP: DADOS E LÓGICA (Mantido)
        =================================================================
    --}}
    @php
        // 1. Imagens
        $parentImages = [];
        if ($product->image_url) $parentImages[] = asset('storage/' . $product->image_url);
        if ($product->gallery && is_array($product->gallery)) {
            foreach ($product->gallery as $img) $parentImages[] = asset('storage/' . $img);
        }
        if (empty($parentImages)) $parentImages[] = 'https://via.placeholder.com/500?text=Sem+Imagem';

        // 2. Variáveis
        $normalize = fn($str) => ucfirst(trim($str));
        $optionsMap = [];
        $normalizedVariants = [];
        $colorImagesFallback = []; 
        $onSaleOptions = []; 
        $bestInitialVariant = null;

        if ($product->variants) {
            foreach ($product->variants as $variant) {
                // A. Limpeza
                $cleanOptions = [];
                if ($variant->options) {
                    foreach ($variant->options as $key => $value) {
                        $cleanKey = $normalize($key);
                        $cleanValue = $normalize($value);
                        $cleanOptions[$cleanKey] = $cleanValue;
                        $optionsMap[$cleanKey][] = $cleanValue;
                    }
                }
                
                $vArray = $variant->toArray();
                $vArray['options'] = $cleanOptions;
                $vArray['is_on_sale'] = $variant->isOnSale();
                
                // B. Estoque
                $vArray['stock'] = (int)($variant->quantity ?? 0);

                // C. Formatação
                $vArray['formatted_price'] = number_format($variant->price, 2, ',', '.');
                $vArray['formatted_sale_price'] = $variant->sale_price ? number_format($variant->sale_price, 2, ',', '.') : null;
                $vArray['discount_percentage'] = ($vArray['is_on_sale'] && $variant->price > 0) ? round((($variant->price - $variant->sale_price) / $variant->price) * 100) : 0;
                $vArray['sale_end_date'] = ($vArray['is_on_sale'] && $variant->sale_end_date) ? $variant->sale_end_date->format('Y-m-d H:i:s') : null;

                // D. Imagens
                $vImages = [];
                if ($variant->image) $vImages[] = asset('storage/' . $variant->image);
                if ($variant->images && is_array($variant->images)) {
                    foreach($variant->images as $img) $vImages[] = asset('storage/' . $img);
                }
                $finalGallery = empty($vImages) ? $parentImages : $vImages;
                $vArray['gallery'] = $finalGallery;

                $cKey = null;
                foreach(['Cor', 'Color', 'COR'] as $k) { if (isset($cleanOptions[$k])) { $cKey = $cleanOptions[$k]; break; } }
                
                if ($cKey) {
                    if (!isset($colorImagesFallback[$cKey]) || !empty($vImages)) {
                        $colorImagesFallback[$cKey] = $finalGallery;
                    }
                }

                if ($vArray['is_on_sale'] && $cKey) $onSaleOptions[] = $cKey;

                // F. Melhor Preço Inicial
                if (!$bestInitialVariant) {
                    $bestInitialVariant = $vArray;
                } else {
                    $currentPrice = $vArray['is_on_sale'] ? $variant->sale_price : $variant->price;
                    $bestPrice = $bestInitialVariant['is_on_sale'] ? ($bestInitialVariant['sale_price'] ?? $bestInitialVariant['price']) : $bestInitialVariant['price'];
                    
                    if ($vArray['is_on_sale'] && !$bestInitialVariant['is_on_sale']) {
                        $bestInitialVariant = $vArray;
                    } elseif (($vArray['is_on_sale'] == $bestInitialVariant['is_on_sale']) && $currentPrice < $bestPrice) {
                        $bestInitialVariant = $vArray;
                    }
                }

                $normalizedVariants[] = $vArray;
            }
        }

        foreach ($optionsMap as $key => $values) $optionsMap[$key] = array_values(array_unique($values));

        // Fallback
        if (!$bestInitialVariant) {
            $bestInitialVariant = [
                'id' => null,
                'price' => $product->base_price,
                'sale_price' => $product->sale_price,
                'is_on_sale' => $product->isOnSale(),
                'formatted_price' => number_format($product->base_price, 2, ',', '.'),
                'formatted_sale_price' => $product->sale_price ? number_format($product->sale_price, 2, ',', '.') : null,
                'discount_percentage' => $product->discount_percentage,
                'sale_end_date' => null,
                'stock' => 100,
                'gallery' => $parentImages,
                'options' => []
            ];
        }

        // Texto Promocional
        $initialPromoText = "";
        if (!empty($onSaleOptions)) {
            $uniqueSaleOptions = array_unique($onSaleOptions);
            $totalOpts = count($optionsMap[array_key_first($optionsMap)] ?? []);
            if (count($uniqueSaleOptions) > 0 && count($uniqueSaleOptions) == $totalOpts) {
                 $initialPromoText = "Oferta válida para todas as opções";
            } elseif (count($uniqueSaleOptions) > 0) {
                $listed = array_slice($uniqueSaleOptions, 0, 3);
                $initialPromoText = "Oferta válida para: " . implode(', ', $listed) . (count($uniqueSaleOptions) > 3 ? "..." : "");
            }
        }

        $alpineConfig = [
            'variants' => $normalizedVariants,
            'optionsMap' => $optionsMap,
            'baseImages' => $parentImages,
            'colorImages' => $colorImagesFallback,
            'initialVariant' => $bestInitialVariant,
            'initialPromoText' => $initialPromoText,
            'isInWishlist' => false 
        ];
    @endphp

    {{-- 
        =================================================================
        VIEW: HTML
        =================================================================
    --}}
    <div class="container mx-auto px-4 py-8 pt-24 md:pt-32"
         x-data="productSelector({{ json_encode($alpineConfig) }})">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16 items-start max-w-7xl mx-auto">
            
            {{-- COLUNA 1: IMAGENS --}}
            <div class="space-y-4">
                <div class="relative w-full ">
                    <div class="relative h-[500px] w-full flex items-center justify-center p-6">
                        <img :src="currentImage" class="w-full h-full object-contain transition-all duration-300" alt="{{ $product->name }}">
                    </div>
                    <button x-show="galleryImages.length > 1" @click="prevImage()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-black p-3 rounded-full shadow-sm border border-gray-100 transition-all opacity-0 group-hover:opacity-100 translate-x-[-10px] group-hover:translate-x-0 z-10"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg></button>
                    <button x-show="galleryImages.length > 1" @click="nextImage()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-black p-3 rounded-full shadow-sm border border-gray-100 transition-all opacity-0 group-hover:opacity-100 translate-x-[10px] group-hover:translate-x-0 z-10"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg></button>
                    <div x-show="galleryImages.length > 1" class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/80 px-3 py-1 rounded-full text-white text-xs font-bold tracking-widest z-10"><span x-text="galleryImages.indexOf(currentImage) + 1"></span> / <span x-text="galleryImages.length"></span></div>
                </div>
                <div class="flex flex-wrap gap-2" x-show="galleryImages.length > 1">
                    <template x-for="(image, index) in galleryImages" :key="index">
                        <button @click="currentImage = image" 
                                class="w-16 h-16 rounded-xl overflow-hidden border bg-white flex items-center justify-center transition-all duration-200" 
                                :class="currentImage === image ? 'border-black ring-1 ring-black' : 'border-gray-200 hover:border-gray-400 opacity-70 hover:opacity-100'">
                            <img :src="image" class="w-full h-full object-contain p-1">
                        </button>
                    </template>
                </div>
            </div>

            {{-- COLUNA 2: INFO --}}
            <div class="flex flex-col space-y-6">
                
                {{-- Cabeçalho --}}
                <div>
                    @if($product->category) <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">{{ $product->category->name }}</p> @endif
                    <h1 class="text-4xl font-black text-black uppercase tracking-tight leading-none mb-2">{{ $product->name }}</h1>
                    
                    <div class="flex items-center space-x-2">
                        <div class="flex text-black scale-75 origin-left">@for($i=0; $i<5; $i++) <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg> @endfor</div>
                        <span class="text-xs text-gray-500">({{ $product->reviews_count ?? 0 }} avaliações)</span>
                    </div>
                </div>
                
                {{-- BLOCO DE PREÇO / OFERTA --}}
                <div class="">
                    <template x-if="displayVariant">
                        <div>
                            <template x-if="displayVariant.is_on_sale">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="text-3xl font-black text-black">R$ <span x-text="displayVariant.formatted_sale_price"></span></span>
                                        <span class="bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">-<span x-text="displayVariant.discount_percentage"></span>%</span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">De:</span>
                                        <span class="text-sm text-gray-400 line-through font-medium">R$ <span x-text="displayVariant.formatted_price"></span></span>
                                    </div>

                                    <div x-show="timerDisplay" class="flex items-center gap-2 text-red-700 bg-red-100/50 px-3 py-2 rounded-lg border border-red-100 w-fit mt-1">
                                        {{-- Aumentei o ícone de w-3 h-3 para w-4 h-4 --}}
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        
                                        {{-- Aumentei de text-[10px] para text-xs (12px) --}}
                                        <span class="text-xs font-bold uppercase tracking-wide">Expira em:</span>
                                        
                                        {{-- Aumentei de text-[10px] para text-sm (14px) para os números ficarem mais legíveis --}}
                                        <span class="text-sm font-black font-mono" x-text="timerDisplay"></span>
                                    </div>
                                </div>
                            </template>
                            
                            <template x-if="!displayVariant.is_on_sale">
                                <div>
                                    <p class="text-[10px] text-gray-500 mb-0.5 font-bold uppercase tracking-wider" x-show="!cartVariant">A partir de</p>
                                    <p class="text-3xl font-black text-black">R$ <span x-text="displayVariant.formatted_price"></span></p>
                                </div>
                            </template>

                            {{-- MENSAGEM PERSISTENTE (Vermelha + Exclamação) --}}
                            <template x-if="promoText">
                                <p class="text-[10px] text-red-600 font-bold mt-2 flex items-center gap-1.5 pt-2  uppercase tracking-wide">
                                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <span x-text="promoText"></span>
                                </p>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- SELETORES --}}
                <div class="space-y-4 max-w-md">
                    @foreach($optionsMap as $optionName => $optionValues)
                        <div>
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-2">{{ $optionName }}</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($optionValues as $value) 
                                    <button @click="selectOption('{{ $optionName }}', '{{ $value }}')" 
                                            class="px-4 py-2 border rounded-lg transition-all text-xs font-bold uppercase tracking-wide min-w-[3rem]" 
                                            :class="selectedOptions['{{ $optionName }}'] === '{{ $value }}' ? 'border-black bg-black text-white' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-400'">
                                        {{ $value }}
                                    </button> 
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- AÇÃO DE COMPRA --}}
               {{-- AÇÃO DE COMPRA (Menos largo: max-w-sm e altura h-12) --}}
                <div class="flex flex-col gap-2 max-w-sm">
                    <form action="{{ route('cart.add', $product->id) }}" method="POST" class="w-full" @submit.prevent="submitCart($el)">
                        @csrf <input type="hidden" name="variant_id" :value="cartVariant ? cartVariant.id : ''">
                        <button type="submit" 
                                class="w-full flex justify-center items-center h-12 px-4 border rounded-xl shadow-md text-sm font-black transition-all duration-200 focus:outline-none uppercase tracking-widest transform active:scale-[0.99]" 
                                :class="getButtonClass()">
                            <span x-text="getButtonLabel()"></span>
                        </button>
                    </form>
                    <div x-show="showError" x-transition class="text-xs text-red-600 font-bold flex items-center gap-1 pl-1" style="display: none;">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        <span x-text="errorMessage"></span>
                    </div>
                </div>

                {{-- FRETE E AÇÕES SECUNDÁRIAS (Compacto max-w-sm) --}}
                <div class="pt-4  max-w-sm space-y-4">
                    {{-- Frete (Altura h-12 para combinar com compra) --}}
                    <div x-data="{ zipCode: '', loading: false, result: null, error: null, async calculate() { const cleanCep = this.zipCode.replace(/\D/g, ''); if (cleanCep.length !== 8) { this.error = 'CEP Inválido'; return; } this.loading = true; this.error = null; this.result = null; try { const response = await axios.post('{{ route('shipping.calculate') }}', { zip_code: cleanCep, product_id: {{ $product->id }} }); this.result = response.data; } catch (e) { this.error = 'Erro ao calcular.'; } finally { this.loading = false; } } }">
                        <div class="flex gap-2 mb-2">
                            <input type="text" x-model="zipCode" @keydown.enter.prevent="calculate()" @input="$el.value = $el.value.replace(/\D/g, '').replace(/^(\d{5})(\d)/, '$1-$2')" placeholder="CEP" class="w-full h-12 px-3 rounded-lg border border-gray-300 bg-white text-xs text-gray-900 focus:border-black focus:ring-black">
                            <button @click="calculate()" :disabled="loading" class="h-12 px-4 border border-black rounded-lg bg-black text-white text-[10px] font-bold uppercase hover:bg-gray-800 transition-all disabled:opacity-50">
                                <span x-show="!loading">OK</span><span x-show="loading">...</span>
                            </button>
                        </div>
                        <div x-show="result" style="display: none;" class="mt-2 text-xs"><template x-for="option in result" :key="option.name"><div class="flex justify-between pb-1"><span><span class="font-bold" x-text="option.name"></span> (<span x-text="option.days"></span> dias)</span><span class="font-bold">R$ <span x-text="new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2 }).format(option.price)"></span></span></div></template></div>
                    </div>

                    {{-- Ícones Secundários (Apenas Ícones, largura fixa) --}}
                    <div class="flex gap-2 justify-start">
                        <button @click="toggleWishlist()" class="w-12 h-12 flex items-center justify-center border rounded-lg transition-all" :class="isInWishlist ? 'bg-red-50 border-red-200 text-red-600' : 'border-gray-200 text-gray-500 hover:border-black hover:text-black'">
                            <svg x-show="isInWishlist" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" /></svg>
                            <svg x-show="!isInWishlist" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                        </button>
                        <button @click="copyLink()" class="w-12 h-12 flex items-center justify-center border border-gray-200 rounded-lg text-gray-500 hover:border-black hover:text-black transition-all">
                            <svg x-show="!copied" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" /></svg>
                            <svg x-show="copied" style="display: none;" class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- RESTO DA PÁGINA (ACORDEONS, RELACIONADOS) --}}
        <div class="border-t border-gray-200 mt-16 max-w-4xl mx-auto">
            <div x-data="{ open: true }" class="border-b border-gray-200"><button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group"><span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Descrição</span><span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span></button><div x-show="open" class="pb-8 prose text-gray-600 max-w-none">{!! $product->description !!}</div></div>
            <div x-data="{ open: false }" class="border-b border-gray-200"><button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group"><span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Características</span><span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span></button><div x-show="open" x-transition class="pb-8 text-gray-600">@if(!empty($product->characteristics) && is_array($product->characteristics))<dl>@foreach($product->characteristics as $key => $value) <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0"><dt class="text-sm font-bold text-gray-900">{{ $key }}</dt><dd class="mt-1 text-sm text-gray-700 sm:col-span-2 sm:mt-0">{{ $value }}</dd></div> @endforeach</dl>@else <p class="italic text-gray-400">Nenhuma informação técnica disponível.</p> @endif</div></div>
            <div x-data="{ open: false }" class="border-b border-gray-200"><button @click="open = !open" class="flex justify-between items-center w-full py-6 text-left focus:outline-none group"><span class="text-xl font-bold text-gray-900 uppercase tracking-wide group-hover:text-gray-600 transition">Avaliações</span><span x-text="open ? '-' : '+'" class="text-3xl font-light text-gray-400 group-hover:text-black transition"></span></button><div x-show="open" x-transition class="pb-8 text-gray-600">@if($product->reviews && $product->reviews->count() > 0)@foreach($product->reviews as $review) <div class="mb-6 border-b border-gray-100 pb-4 last:border-0"><div class="flex items-center justify-between mb-2"><p class="font-bold text-gray-900">{{ $review->user->name ?? 'Cliente' }}</p><span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</span></div><div class="flex text-yellow-500 mb-2">@for($i=0; $i<$review->rating; $i++) ★ @endfor</div><p class="text-sm leading-relaxed">{{ $review->content }}</p></div> @endforeach @else <p class="italic text-gray-500">Ainda não há avaliações.</p> @endif</div></div>
        </div>

        <div class="mt-24">
            <h2 class="text-2xl font-black uppercase tracking-widest mb-10 text-center">Você também pode gostar</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach($relatedProducts as $related)
                    <div class="block group relative" x-data="{ currentImage: '{{ Storage::url($related->image_url) }}', originalImage: '{{ Storage::url($related->image_url) }}', hovering: false }" @mouseenter="hovering = true" @mouseleave="hovering = false">
                        <a href="{{ route('shop.product', $related->slug) }}" class="block cursor-pointer"><div class="relative overflow-hidden rounded-lg aspect-[3/4] mb-4 bg-white flex items-center justify-center">@if($related->created_at->diffInDays(now()) < 30)<div class="absolute top-3 left-3 bg-black text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest z-10 shadow-sm">Novo</div>@endif<img :src="currentImage" class="object-contain w-full h-full transition duration-500 p-2" :class="hovering ? 'scale-105' : ''">@if($related->variants->whereNotNull('image')->count() > 0)<div x-show="hovering" x-transition class="absolute bottom-3 left-0 right-0 flex justify-center gap-2 px-2 z-20 flex-wrap">@foreach($related->variants->whereNotNull('image')->unique('image')->take(4) as $variant)<div @mouseenter="currentImage = '{{ Storage::url($variant->image) }}'" @mouseleave="currentImage = originalImage" class="w-10 h-10 rounded-md border border-gray-200 shadow-sm overflow-hidden cursor-pointer bg-white hover:border-black transition-all transform hover:scale-110 flex items-center justify-center"><img src="{{ Storage::url($variant->image) }}" class="w-full h-full object-contain p-0.5"></div>@endforeach</div>@endif</div><div class="text-center space-y-1"><h3 class="font-bold text-gray-900 group-hover:text-gray-600 transition">{{ $related->name }}</h3><div class="mt-1">@if($related->isOnSale())<div class="flex flex-col items-center justify-center gap-0.5"><span class="font-bold text-red-600 text-lg leading-tight">R$ {{ number_format($related->sale_price, 2, ',', '.') }}</span><div class="flex items-center gap-2"><span class="text-xs text-gray-400 line-through">R$ {{ number_format($related->base_price, 2, ',', '.') }}</span><span class="bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 rounded">-{{ $related->discount_percentage }}%</span></div></div>@else<p class="text-gray-500">R$ {{ number_format($related->base_price, 2, ',', '.') }}</p>@endif</div></div></a><div class="pt-2 h-10 flex items-center justify-center"><form action="{{ route('cart.add', $related->id) }}" method="POST">@csrf <button type="submit" class="bg-black text-white border border-black px-8 py-2 rounded-xl uppercase font-bold text-xs tracking-widest shadow-md opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 hover:bg-white hover:text-black transition-all duration-300">Adicionar ao Carrinho</button></form></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- SCRIPT ALPINE JS --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productSelector', (config) => ({
                variants: config.variants,
                optionsMap: config.optionsMap,
                selectedOptions: {},
                cartVariant: null, 
                displayVariant: config.initialVariant, 
                promoText: config.initialPromoText,
                colorImages: config.colorImages,
                currentImage: config.baseImages[0],
                galleryImages: config.baseImages,
                timerDisplay: '',
                timerInterval: null,
                showError: false,
                errorMessage: '',
                isInWishlist: config.isInWishlist,
                copied: false,

                init() {
                    if (this.displayVariant && this.displayVariant.gallery) {
                        this.galleryImages = this.displayVariant.gallery;
                        this.currentImage = this.galleryImages[0];
                    }
                    if (Object.keys(this.optionsMap).length === 0 && this.variants.length > 0) {
                        this.cartVariant = this.variants[0];
                        this.displayVariant = this.cartVariant;
                    }
                    this.updateTimerFromDisplay();
                },

                async toggleWishlist() {
                    this.isInWishlist = !this.isInWishlist;
                    try { } catch (e) {
                        this.isInWishlist = !this.isInWishlist;
                        alert('Erro ao adicionar aos favoritos.');
                    }
                },

                copyLink() {
                    navigator.clipboard.writeText(window.location.href);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                },

                selectOption(key, value) {
                    this.selectedOptions[key] = value;
                    this.showError = false; 
                    if (this.colorImages[value]) {
                        this.galleryImages = this.colorImages[value];
                        this.currentImage = this.galleryImages[0];
                    }
                    this.updateDisplayData();
                    this.checkCartReadiness();
                },

                updateDisplayData() {
                    let candidates = this.variants.filter(v => {
                        for (const [k, val] of Object.entries(this.selectedOptions)) {
                            if (v.options[k] !== val) return false;
                        }
                        return true;
                    });
                    if (candidates.length > 0) {
                        candidates.sort((a, b) => {
                            if (a.is_on_sale && !b.is_on_sale) return -1;
                            if (!a.is_on_sale && b.is_on_sale) return 1;
                            let pA = a.is_on_sale ? (a.sale_price || a.price) : a.price;
                            let pB = b.is_on_sale ? (b.sale_price || b.price) : b.price;
                            return pA - pB;
                        });
                        this.displayVariant = candidates[0];
                        this.updateTimerFromDisplay();
                    }
                },

                checkCartReadiness() {
                    const requiredKeys = Object.keys(this.optionsMap);
                    const selectedKeys = Object.keys(this.selectedOptions);
                    if (requiredKeys.length !== selectedKeys.length) { this.cartVariant = null; return; }
                    const exactMatch = this.variants.find(v => {
                        for (const [k, val] of Object.entries(this.selectedOptions)) { if (v.options[k] !== val) return false; }
                        return true;
                    });
                    if (exactMatch) this.cartVariant = exactMatch; else this.cartVariant = null;
                },

                updateTimerFromDisplay() {
                    this.stopTimer();
                    if (this.displayVariant && this.displayVariant.is_on_sale && this.displayVariant.sale_end_date) {
                        this.startTimer(this.displayVariant.sale_end_date);
                    }
                },

                getButtonLabel() { if (this.cartVariant && this.cartVariant.stock <= 0) return 'ESGOTADO'; return 'COMPRAR AGORA'; },
                getButtonClass() { if (this.cartVariant && this.cartVariant.stock <= 0) return 'bg-gray-200 text-gray-400 border-gray-200 cursor-not-allowed'; return 'bg-black hover:bg-white hover:text-black text-white border-black'; },
                
                submitCart(form) { 
                    if (this.cartVariant && this.cartVariant.stock > 0) { form.submit(); } 
                    else {
                        this.showError = true;
                        if (this.cartVariant && this.cartVariant.stock <= 0) this.errorMessage = "Produto esgotado."; 
                        else {
                            const allOptions = Object.keys(this.optionsMap);
                            const selected = Object.keys(this.selectedOptions);
                            const missing = allOptions.filter(opt => !selected.includes(opt));
                            if (missing.length > 0) this.errorMessage = "Por favor, selecione: " + missing.join(', ');
                            else this.errorMessage = "Opção indisponível.";
                        }
                    }
                },
                
                startTimer(date) { const end = new Date(date).getTime(); this.calcTime(end); this.timerInterval = setInterval(() => { this.calcTime(end); }, 1000); },
                calcTime(end) {
                    const now = new Date().getTime(); const d = end - now;
                    if(d < 0) { this.timerDisplay = "EXPIRADO"; this.stopTimer(); return; }
                    const days = Math.floor(d / (1000 * 60 * 60 * 24)); const hours = Math.floor((d % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((d % (1000 * 60 * 60)) / (1000 * 60)); const seconds = Math.floor((d % (1000 * 60)) / 1000);
                    this.timerDisplay = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                },
                stopTimer() { clearInterval(this.timerInterval); this.timerInterval = null; this.timerDisplay = ''; },
                nextImage() { const i = this.galleryImages.indexOf(this.currentImage); this.currentImage = this.galleryImages[(i+1)%this.galleryImages.length]; },
                prevImage() { const i = this.galleryImages.indexOf(this.currentImage); this.currentImage = this.galleryImages[i===0 ? this.galleryImages.length-1 : i-1]; }
            }));
        });
    </script>
</x-layout>