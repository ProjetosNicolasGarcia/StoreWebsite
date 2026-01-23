@props(['category', 'level' => 0])

{{-- 
    [VISUAL] Aumentei o contraste da borda de 'border-gray-50' para 'border-gray-200' 
    para criar as linhas claras que você pediu.
--}}
<li class="border-b border-gray-200 last:border-0" x-data="{ expanded: false }">
    
    {{-- Container Principal --}}
    <div class="flex items-center justify-between pr-4 py-3 transition select-none hover:bg-gray-50"
         {{-- O padding aumenta conforme o nível para dar o efeito de escada --}}
         style="padding-left: {{ $level * 1.5 + 1.5 }}rem;">
        
        {{-- 
            [AÇÃO 1] O Link:
            Ao clicar aqui, o usuário é DIRECIONADO para a página.
            Removemos qualquer @click daqui.
        --}}
        <a href="{{ route('shop.category', $category->slug) }}" 
           class="flex-1 font-bold text-gray-800 uppercase text-sm tracking-wide transition-colors duration-200 hover:text-red-600">
            {{ $category->name }}
        </a>

        {{-- 
            [AÇÃO 2] A Seta:
            Só aparece se tiver filhos.
            O @click="expanded = !expanded" está EXCLUSIVO aqui.
            Adicionei um hover circular e background para indicar que é clicável.
        --}}
        @if($category->children->isNotEmpty())
            <button @click="expanded = !expanded" 
                    type="button"
                    class="p-2 ml-2 text-gray-400 hover:text-black hover:bg-gray-200 rounded-full transition-all duration-200 focus:outline-none"
                    :class="expanded ? 'rotate-180 bg-gray-100 text-black' : ''">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Container dos Filhos (Recursivo) --}}
    @if($category->children->isNotEmpty())
        <div x-show="expanded" 
             x-collapse 
             style="display: none;">
            
            {{-- 
                [VISUAL] Adicionei 'border-t border-gray-200' para separar o pai dos filhos
                e 'bg-gray-50/30' para dar uma leve cor de fundo na sub-área.
            --}}
            <ul class="flex flex-col border-t border-gray-200 bg-gray-50/50">
                @foreach($category->children as $child)
                    {{-- Chama a si mesmo para o próximo nível --}}
                    <x-category-item :category="$child" :level="$level + 1" />
                @endforeach
            </ul>
        </div>
    @endif
</li>