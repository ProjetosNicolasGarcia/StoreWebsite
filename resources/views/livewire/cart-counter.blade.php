{{-- 
    O atributo aria-live="polite" fará o leitor de tela avisar caso o número mude.
    O aria-label foi formatado para ler corretamente a quantidade no plural ou singular.
    aria-hidden esconde o elemento dos leitores quando o valor é 0.
--}}
<span 
    class="{{ $count > 0 ? 'flex' : 'hidden' }} absolute -top-1 -right-1 bg-red-600 text-white text-[10px] lg:text-xs font-bold rounded-full h-4 w-4 lg:h-5 lg:w-5 items-center justify-center"
    aria-live="polite"
    aria-atomic="true"
    aria-label="{{ $count }} {{ $count == 1 ? 'item' : 'itens' }} no carrinho"
    @if($count == 0) aria-hidden="true" @endif
>
    {{ $count }}
</span>