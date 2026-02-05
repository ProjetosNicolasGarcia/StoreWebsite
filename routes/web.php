<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\StoreAuthController;
use App\Http\Controllers\ProfileController;

// =========================================================================
// ROTAS PÚBLICAS (Home, Loja, Carrinho)
// =========================================================================

// Página Inicial
Route::get('/', [HomeController::class, 'index'])->name('home');

// Pesquisa e Sugestões
Route::get('/search/suggestions', [ShopController::class, 'suggestions'])->name('shop.suggestions');
Route::get('/search', [ShopController::class, 'search'])->name('shop.search');

// Produtos e Categorias
Route::get('/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/collections/{slug}', [ShopController::class, 'collection'])->name('shop.collection');
Route::get('/product/{slug}', [ShopController::class, 'show'])->name('shop.product');
Route::get('/offers', [ShopController::class, 'offers'])->name('shop.offers');

// Carrinho de Compras
Route::get('/carrinho', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrinho/adicionar/{id}', [CartController::class, 'add'])->name('cart.add');
Route::delete('/carrinho/remover/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::put('/carrinho/update/{id}', [CartController::class, 'update'])->name('cart.update');

// Cálculo de Frete
Route::post('/shipping/calculate', [ShopController::class, 'simulateShipping'])->name('shipping.calculate');

// =========================================================================
// PÁGINAS INSTITUCIONAIS E DE APOIO
// =========================================================================

// Termos de Uso
Route::get('/termos-de-uso', function () {
    return view('pages.text-page', [
        'title' => 'Termos de Uso',
        'content' => '
            <p>Bem-vindo à <strong>Minha Loja</strong>. Estes termos e condições descrevem as regras e regulamentos para o uso do nosso site.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">1. Aceitação dos Termos</h3>
            <p>Ao acessar este site, assumimos que você aceita estes termos e condições na íntegra. Não continue a usar o site da Minha Loja se você não aceitar todos os termos e condições declarados nesta página.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">2. Licença</h3>
            <p>Exceto indicação em contrário, a Minha Loja e/ou seus licenciadores detêm os direitos de propriedade intelectual de todo o material neste site.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">3. Isenção de Responsabilidade</h3>
            <p>Na extensão máxima permitida pela lei aplicável, excluímos todas as representações, garantias e condições relacionadas ao nosso site.</p>
        '
    ]);
})->name('pages.terms');

// Política de Privacidade
Route::get('/politica-de-privacidade', function () {
    return view('pages.text-page', [
        'title' => 'Política de Privacidade',
        'content' => '
            <p>A sua privacidade é extremamente importante para nós. É política da <strong>Minha Loja</strong> respeitar a sua privacidade em relação a qualquer informação sua que possamos coletar no site.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">1. Informações que Coletamos</h3>
            <p>Solicitamos informações pessoais apenas quando realmente precisamos delas para lhe fornecer um serviço.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">2. Uso das Informações</h3>
            <p>Podemos usar as informações coletadas para fornecer, operar e manter nosso site.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">3. Segurança</h3>
            <p>Apenas retemos as informações coletadas pelo tempo necessário para fornecer o serviço solicitado.</p>
        '
    ]);
})->name('pages.privacy');

// Política de Cookies
Route::get('/politica-de-cookies', function () {
    return view('pages.text-page', [
        'title' => 'Política de Cookies',
        'content' => '
            <p>Esta é a Política de Cookies da Minha Loja, acessível a partir da nossa página principal.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">O que são Cookies?</h3>
            <p>Como é prática comum em quase todos os sites profissionais, este site usa cookies para melhorar sua experiência.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">Como usamos os cookies</h3>
            <p>Utilizamos cookies por vários motivos, incluindo funcionamento do carrinho e análise de tráfego.</p>
        '
    ]);
})->name('pages.cookies');

// Acessibilidade
Route::get('/acessibilidade', function () {
    return view('pages.text-page', [
        'title' => 'Declaração de Acessibilidade',
        'content' => '
            <p>A <strong>Minha Loja</strong> compromete-se a garantir a acessibilidade digital para pessoas com deficiência.</p>
            <h3 class="text-xl font-bold text-black mt-8 mb-2">Feedback</h3>
            <p>E-mail: acessibilidade@minhaloja.com.br</p>
        '
    ]);
})->name('pages.accessibility');

// FAQ (Dúvidas Gerais)
Route::get('/duvidas-gerais', function () {
    $faqTopics = [
        [
            'title' => 'Entregas',
            'slug' => 'entregas',
            'questions' => [
                ['question' => 'Qual é o prazo de entrega?', 'answer' => 'O prazo varia de 3 a 10 dias úteis dependendo da região.'],
                ['question' => 'Como rastrear meu pedido?', 'answer' => 'Você receberá o código por e-mail após o envio.'],
                ['question' => 'Vocês entregam em todo o Brasil?', 'answer' => 'Sim, enviamos para todo o território nacional.']
            ]
        ],
        [
            'title' => 'Pagamentos',
            'slug' => 'pagamentos',
            'questions' => [
                ['question' => 'Quais são as formas de pagamento?', 'answer' => 'Cartão de crédito, boleto e PIX com desconto.'],
                ['question' => 'É seguro digitar meu cartão no site?', 'answer' => 'Totalmente. Utilizamos criptografia SSL.']
            ]
        ],
        [
            'title' => 'Trocas e Devoluções',
            'slug' => 'trocas',
            'questions' => [
                ['question' => 'Como faço para devolver um produto?', 'answer' => 'Você tem até 7 dias corridos para solicitar a devolução.'],
                ['question' => 'A troca tem custo?', 'answer' => 'A primeira troca é por nossa conta!']
            ]
        ]
    ];
    return view('pages.faq-page', compact('faqTopics'));
})->name('pages.faq');

// Fale Conosco
Route::get('/fale-conosco', function () {
    return view('pages.contact');
})->name('pages.contact');

Route::post('/fale-conosco', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|min:3',
        'email' => 'required|email',
        'subject' => 'required',
        'order_number' => 'nullable|string',
        'message' => 'required|min:10',
    ]);
    // Mail::to('admin@minhaloja.com.br')->send(new ContactMail($validated));
    return back()->with('success', 'Sua mensagem foi enviada com sucesso! Responderemos em breve.');
})->name('pages.contact.send');

// =========================================================================
// AUTENTICAÇÃO (GUEST) - Apenas para visitantes não logados
// =========================================================================

Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [StoreAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [StoreAuthController::class, 'login']);

    // Registro
    Route::get('/register', [StoreAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [StoreAuthController::class, 'register']);

    // 2FA (Verificação de Código)
    Route::get('/auth/2fa', [StoreAuthController::class, 'showTwoFactorForm'])->name('auth.two-factor');
    Route::post('/auth/2fa', [StoreAuthController::class, 'verifyTwoFactor']);

    // Google Login
    Route::get('/auth/google', [StoreAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [StoreAuthController::class, 'handleGoogleCallback']);

    // Recuperação de Senha
    Route::get('/forgot-password', function () {
        return view('auth.forgot-password'); // Necessário criar esta view se ainda não existir
    })->name('password.request');
    
    Route::post('/forgot-password', [StoreAuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [StoreAuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [StoreAuthController::class, 'resetPassword'])->name('password.update');
});

// =========================================================================
// ÁREA DO CLIENTE (AUTH) - Protegido por Login
// =========================================================================

Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [StoreAuthController::class, 'logout'])->name('logout');

    // Minha Conta
    Route::prefix('minha-conta')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('profile.index');
        Route::put('/update', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/pedidos', [ProfileController::class, 'orders'])->name('profile.orders');
        Route::get('/enderecos', [ProfileController::class, 'addresses'])->name('profile.addresses');
        Route::post('/enderecos', [ProfileController::class, 'storeAddress'])->name('profile.address.store');
        Route::delete('/enderecos/{id}', [ProfileController::class, 'destroyAddress'])->name('profile.address.delete');
    });

    
});

Route::middleware(['auth'])->group(function () {
    // Rotas para completar perfil (Middleware EnsureProfileIsComplete vai permitir estas)
    Route::get('/completar-perfil', [StoreAuthController::class, 'showCompleteProfile'])->name('auth.complete-profile');
    Route::post('/completar-perfil', [StoreAuthController::class, 'updateProfile'])->name('auth.update-profile');

    // ... suas outras rotas de perfil ...
});