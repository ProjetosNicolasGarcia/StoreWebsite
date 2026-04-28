@props(['category', 'level' => 0])

<li class="border-b border-gray-200 last:border-0" x-data="{ expanded: false }">
    
    {{-- Container Principal --}}
    <div class="flex items-center justify-between pr-4 py-4 lg:py-3 transition select-none hover:bg-gray-50"
         style="padding-left: {{ $level * 1.5 + 1.5 }}rem;">
        
        {{-- [UX FIX] Adicionado cursor-pointer na tag 'a' --}}
        <a href="{{ route('shop.category', $category->slug) }}" 
           aria-label="Ver produtos da categoria {{ $category->name }}"
           class="flex-1 font-bold text-gray-900 uppercase text-base lg:text-sm tracking-wide transition-all duration-200 hover:underline underline-offset-4 cursor-pointer focus:outline-none focus:ring-2 focus:ring-black">
            {{ $category->name }}
        </a>
  
        @if($category->children->isNotEmpty())
            {{-- [UX FIX] Adicionado cursor-pointer e hover:scale-110 --}}
            <button @click="expanded = !expanded" 
                    type="button"
                    aria-label="Subcategorias de {{ $category->name }}"
                    :aria-expanded="expanded.toString()"
                    aria-controls="cat-menu-{{ $category->id }}"
                    class="w-10 h-10 lg:w-8 lg:h-8 flex items-center justify-center ml-2 text-gray-400 hover:text-black hover:bg-gray-200 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-black flex-shrink-0 cursor-pointer hover:scale-110"
                    :class="expanded ? 'rotate-180 bg-gray-100 text-black' : ''">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:h-4 lg:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Container dos Filhos (Recursivo) --}}
    @if($category->children->isNotEmpty())
        <div id="cat-menu-{{ $category->id }}"
             x-show="expanded" 
             x-collapse 
             style="display: none;">
            
            <ul aria-label="Subcategorias de {{ $category->name }}" class="flex flex-col border-t border-gray-200 bg-white">
                @foreach($category->children as $child)
                    <x-category-item :category="$child" :level="$level + 1" />
                @endforeach
            </ul>
        </div>
    @endif
</li>