<x-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8" role="main" aria-labelledby="2fa-heading">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg">
            <div>
                <h2 id="2fa-heading" class="mt-6 text-center text-3xl font-extrabold text-gray-900">Verificação de Segurança</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Enviamos um código de 6 dígitos para o seu email.
                </p>
            </div>

            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="status" aria-live="polite">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div id="error-summary" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert" aria-live="assertive">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="mt-8 space-y-6" action="{{ route('auth.two-factor') }}" method="POST" aria-labelledby="2fa-heading">
                @csrf
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="code" class="sr-only">Código de Verificação</label>
                        <input id="code" name="two_factor_code" type="text" required 
                               aria-required="true"
                               aria-invalid="{{ $errors->any() ? 'true' : 'false' }}"
                               aria-describedby="{{ $errors->any() ? 'error-summary' : '' }}"
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-lg text-center tracking-[0.5em]" 
                               placeholder="000000">
                    </div>
                </div>

                <div>
                    <button type="submit" aria-label="Verificar código de segurança" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors cursor-pointer">
                        Verificar Código
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layout>