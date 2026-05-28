{{-- resources/views/livewire/create-ticket.blade.php --}}

<div class="container mx-auto px-4 pt-48 pb-24 bg-white min-h-screen">
    <div class="max-w-4xl mx-auto">
        
        {{-- Controle de Retorno --}}
        <a href="{{ route('help') }}" class="text-xs font-black uppercase tracking-[0.2em] text-gray-500 hover:text-black transition-colors mb-8 inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Voltar para Central de Ajuda
        </a>

        <div class=" p-8 md:p-16 border border-gray-200">
            <h1 class="text-3xl sm:text-4xl font-black uppercase tracking-tight text-gray-900 mb-12 text-center">
                Abertura de Ticket
            </h1>
            
            @if (session()->has('success'))
                <div class="mb-8 p-6 bg-green-50 border-l-4 border-green-500 text-green-700 font-bold text-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-8 p-6 bg-red-50 border-l-4 border-red-500 text-red-700 font-bold text-lg">
                    {{ session('error') }}
                </div>
            @endif

            <form wire:submit.prevent="submitSupport" class="space-y-8">
                @guest
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-500 mb-3">Nome</label>
                            <input type="text" wire:model="name" class="w-full bg-white border border-gray-300 p-5 text-lg rounded-none focus:outline-none focus:ring-0 focus:border-black transition-colors hover:border-black">
                            @error('name') 
                                <p class="text-red-500 text-xs font-bold mt-2 uppercase">{{ $message }}</p> 
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase text-gray-500 mb-3">E-mail</label>
                            <input type="email" wire:model="email" class="w-full bg-white border border-gray-300 p-5 text-lg rounded-none focus:outline-none focus:ring-0 focus:border-black transition-colors hover:border-black">
                            @error('email') 
                                <p class="text-red-500 text-xs font-bold mt-2 uppercase">{{ $message }}</p> 
                            @enderror
                        </div>
                    </div>
                @endguest

                @auth
                    @if($order_id)
                        <div class="bg-white p-6 border border-gray-200">
                            <p class="text-xs font-black uppercase text-gray-500 mb-1">Referente ao Pedido</p>
                            <p class="text-xl font-black text-gray-900">#{{ str_pad($order_id, 6, '0', STR_PAD_LEFT) }}</p>
                        </div>
                    @endif
                @endauth

                <div>
                    <label class="block text-xs font-black uppercase text-gray-500 mb-3">Assunto</label>
                    <input type="text" wire:model="subject" class="w-full bg-white border border-gray-300 p-5 text-lg rounded-none focus:outline-none focus:ring-0 focus:border-black transition-colors hover:border-black">
                    @error('subject') 
                        <p class="text-red-500 text-xs font-bold mt-2 uppercase">{{ $message }}</p> 
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-black uppercase text-gray-500 mb-3">Mensagem</label>
                    <textarea wire:model="message" rows="8" class="w-full bg-white border border-gray-300 p-5 text-lg rounded-none focus:outline-none focus:ring-0 focus:border-black transition-colors hover:border-black"></textarea>
                    @error('message') 
                        <p class="text-red-500 text-xs font-bold mt-2 uppercase">{{ $message }}</p> 
                    @enderror
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full block text-center bg-black text-white border-2 border-black px-10 py-6 font-black uppercase tracking-widest text-base sm:text-lg hover:bg-white hover:text-black transition-colors duration-300">
                        Enviar Mensagem
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>