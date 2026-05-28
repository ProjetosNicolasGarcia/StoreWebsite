
<div class="border-b border-gray-200 py-6 px-4 lg:px-0">
    <h3 class="flow-root mb-4">
        <span class="font-medium text-gray-900">Preço</span>
    </h3>
    <div class="flex items-end gap-2">

        {{-- Mínimo --}}
        <div class="flex-1"
            x-data="{
                display: '',
                init() {
                    if ($wire.minPrice) this.display = this.fmt($wire.minPrice);
                },
                fmt(n) {
                    return parseFloat(n).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                },
                onInput(e) {
                    const digits = e.target.value.replace(/\D/g, '');
                    if (!digits) { this.display = ''; $wire.set('minPrice', null); return; }
                    const n = parseInt(digits) / 100;
                    this.display = this.fmt(n);
                    $wire.set('minPrice', n);
                }
            }">
            <label class="block text-xs font-medium text-gray-500 mb-1">De</label>
            <input
                type="text"
                x-model="display"
                x-on:input="onInput($event)"
                placeholder="R$ 0,00"
                class="w-full border border-gray-300 text-sm px-3 py-2 shadow-sm
                       focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500
                       [appearance:textfield]
                       [&::-webkit-outer-spin-button]:appearance-none
                       [&::-webkit-inner-spin-button]:appearance-none"
            >
        </div>

        <span class="pb-2.5 text-gray-400 text-sm select-none">—</span>

        {{-- Máximo --}}
        <div class="flex-1"
            x-data="{
                display: '',
                init() {
                    if ($wire.maxPrice) this.display = this.fmt($wire.maxPrice);
                },
                fmt(n) {
                    return parseFloat(n).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                },
                onInput(e) {
                    const digits = e.target.value.replace(/\D/g, '');
                    if (!digits) { this.display = ''; $wire.set('maxPrice', null); return; }
                    const n = parseInt(digits) / 100;
                    this.display = this.fmt(n);
                    $wire.set('maxPrice', n);
                }
            }">
            <label class="block text-xs font-medium text-gray-500 mb-1">Até</label>
            <input
                type="text"
                x-model="display"
                x-on:input="onInput($event)"
                placeholder="R$ 0,00"
                class="w-full border border-gray-300 text-sm px-3 py-2 shadow-sm
                       focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500
                       [appearance:textfield]
                       [&::-webkit-outer-spin-button]:appearance-none
                       [&::-webkit-inner-spin-button]:appearance-none"
            >
        </div>

    </div>
</div>

@foreach($availableCharacteristics as $key => $values)
<div class="border-b border-gray-200 py-6 px-4 lg:px-0" x-data="{ open: true }">
    <h3 class="-my-3 flow-root">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between bg-white py-3 text-sm text-gray-400 hover:text-gray-500">
            <span class="font-medium text-gray-900">{{ ucfirst($key) }}</span>
            <span class="ml-6 flex items-center">
                <svg x-show="!open" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                <svg x-show="open" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4 10a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H4.75A.75.75 0 014 10z" clip-rule="evenodd" /></svg>
            </span>
        </button>
    </h3>
    <div x-show="open" class="pt-6">
        <div class="space-y-4">
            @foreach($values as $val)
            <div class="flex items-center">
                {{-- ✅ alterado: wire:model e value usam nova sintaxe segura. O id usa slug para evitar conflitos DOM --}}
                <input id="char-{{ Str::slug($key) }}-{{ Str::slug($val) }}" 
                       type="checkbox" 
                       value="char:::{{ $key }}:::{{ $val }}" 
                       wire:model.live="selectedFilters" 
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="char-{{ Str::slug($key) }}-{{ Str::slug($val) }}" class="ml-3 text-sm text-gray-600">{{ $val }}</label>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach

@foreach($availableOptions as $key => $values)
<div class="border-b border-gray-200 py-6 px-4 lg:px-0" x-data="{ open: true }">
    <h3 class="-my-3 flow-root">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between bg-white py-3 text-sm text-gray-400 hover:text-gray-500">
            <span class="font-medium text-gray-900">{{ ucfirst($key) }}</span>
            <span class="ml-6 flex items-center">
                <svg x-show="!open" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                <svg x-show="open" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4 10a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H4.75A.75.75 0 014 10z" clip-rule="evenodd" /></svg>
            </span>
        </button>
    </h3>
    <div x-show="open" class="pt-6">
        <div class="space-y-4">
            @foreach($values as $val)
            <div class="flex items-center">
                {{-- ✅ alterado: ID único e value blindado --}}
                <input id="opt-{{ Str::slug($key) }}-{{ Str::slug($val) }}" 
                       type="checkbox" 
                       value="opt:::{{ $key }}:::{{ $val }}" 
                       wire:model.live="selectedFilters" 
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="opt-{{ Str::slug($key) }}-{{ Str::slug($val) }}" class="ml-3 text-sm text-gray-600">{{ $val }}</label>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach