<?php


use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail;
use App\Http\Controllers\CartController;
use App\Http\Controllers\StoreAuthController;
use App\Http\Controllers\ProfileController;

// Rota da Página Inicial
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/search/suggestions', [ShopController::class, 'suggestions'])->name('shop.suggestions'); // Rota da API
Route::get('/search', [ShopController::class, 'search'])->name('shop.search');

// Rotas da Loja
Route::get('/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/collections/{slug}', [ShopController::class, 'collection'])->name('shop.collection');

// Rota do Produto (A que criamos agora)
Route::get('/product/{slug}', [ShopController::class, 'show'])->name('shop.product');

/// --- PÁGINAS INSTITUCIONAIS (AVISOS LEGAIS) ---

// 1. Termos de Uso
Route::get('/termos-de-uso', function () {
    return view('pages.text-page', [
        'title' => 'Termos de Uso',
        'content' => '
            <p>Bem-vindo à <strong>Minha Loja</strong>. Estes termos e condições descrevem as regras e regulamentos para o uso do nosso site.</p>
            
            <h3 class="text-xl font-bold text-black mt-8 mb-2">1. Aceitação dos Termos</h3>
            <p>Ao acessar este site, assumimos que você aceita estes termos e condições na íntegra. Não continue a usar o site da Minha Loja se você não aceitar todos os termos e condições declarados nesta página.</p>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">2. Licença</h3>
            <p>Exceto indicação em contrário, a Minha Loja e/ou seus licenciadores detêm os direitos de propriedade intelectual de todo o material neste site. Todos os direitos de propriedade intelectual são reservados. Você pode visualizar e/ou imprimir páginas para seu uso pessoal, sujeito às restrições definidas nestes termos e condições.</p>
            <p>Você não deve:</p>
            <ul class="list-disc pl-5 space-y-2 mt-2">
                <li>Republicar material deste site.</li>
                <li>Vender, alugar ou sublicenciar material deste site.</li>
                <li>Reproduzir, duplicar ou copiar material deste site.</li>
            </ul>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">3. Isenção de Responsabilidade</h3>
            <p>Na extensão máxima permitida pela lei aplicável, excluímos todas as representações, garantias e condições relacionadas ao nosso site e ao uso deste site (incluindo, sem limitação, quaisquer garantias implícitas por lei em relação à qualidade satisfatória, adequação à finalidade e/ou o uso de cuidados e habilidades razoáveis).</p>
        '
    ]);
})->name('pages.terms');


// 2. Política de Privacidade
Route::get('/politica-de-privacidade', function () {
    return view('pages.text-page', [
        'title' => 'Política de Privacidade',
        'content' => '
            <p>A sua privacidade é extremamente importante para nós. É política da <strong>Minha Loja</strong> respeitar a sua privacidade em relação a qualquer informação sua que possamos coletar no site.</p>
            
            <h3 class="text-xl font-bold text-black mt-8 mb-2">1. Informações que Coletamos</h3>
            <p>Solicitamos informações pessoais apenas quando realmente precisamos delas para lhe fornecer um serviço. Fazemo-lo por meios justos e legais, com o seu conhecimento e consentimento. Também informamos por que estamos coletando e como será usado.</p>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">2. Uso das Informações</h3>
            <p>Podemos usar as informações coletadas para:</p>
            <ul class="list-disc pl-5 space-y-2 mt-2">
                <li>Fornecer, operar e manter nosso site;</li>
                <li>Melhorar, personalizar e expandir nosso site;</li>
                <li>Entender e analisar como você usa nosso site;</li>
                <li>Desenvolver novos produtos, serviços, recursos e funcionalidades.</li>
            </ul>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">3. Segurança</h3>
            <p>Apenas retemos as informações coletadas pelo tempo necessário para fornecer o serviço solicitado. Quando armazenamos dados, protegemos dentro de meios comercialmente aceitáveis ​​para evitar perdas e roubos, bem como acesso, divulgação, cópia, uso ou modificação não autorizados.</p>
        '
    ]);
})->name('pages.privacy');


// 3. Política de Cookies
Route::get('/politica-de-cookies', function () {
    return view('pages.text-page', [
        'title' => 'Política de Cookies',
        'content' => '
            <p>Esta é a Política de Cookies da Minha Loja, acessível a partir da nossa página principal.</p>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">O que são Cookies?</h3>
            <p>Como é prática comum em quase todos os sites profissionais, este site usa cookies, que são pequenos arquivos baixados no seu computador, para melhorar sua experiência. Esta página descreve quais informações eles coletam, como as usamos e por que às vezes precisamos armazenar esses cookies.</p>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">Como usamos os cookies</h3>
            <p>Utilizamos cookies por vários motivos detalhados abaixo. Infelizmente, na maioria dos casos, não existem opções padrão do setor para desativar os cookies sem desativar completamente a funcionalidade e os recursos que eles adicionam a este site. É recomendável que você deixe todos os cookies se não tiver certeza se precisa ou não deles, caso sejam usados ​​para fornecer um serviço que você usa.</p>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">Tipos de Cookies que utilizamos</h3>
            <ul class="list-disc pl-5 space-y-2 mt-2">
                <li><strong>Cookies Essenciais:</strong> Necessários para o funcionamento do carrinho de compras e checkout.</li>
                <li><strong>Cookies de Análise:</strong> Usados para entender como os visitantes interagem com o site (ex: Google Analytics).</li>
                <li><strong>Cookies de Marketing:</strong> Usados para rastrear visitantes em sites para exibir anúncios relevantes.</li>
            </ul>
        '
    ]);
})->name('pages.cookies');


// 4. Acessibilidade
Route::get('/acessibilidade', function () {
    return view('pages.text-page', [
        'title' => 'Declaração de Acessibilidade',
        'content' => '
            <p>A <strong>Minha Loja</strong> compromete-se a garantir a acessibilidade digital para pessoas com deficiência. Estamos continuamente melhorando a experiência do usuário para todos e aplicando os padrões de acessibilidade relevantes.</p>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">Status de Conformidade</h3>
            <p>As Diretrizes de Acessibilidade de Conteúdo da Web (WCAG) definem requisitos para designers e desenvolvedores para melhorar a acessibilidade para pessoas com deficiência. Nosso objetivo é estar em conformidade parcial com a WCAG 2.1 nível AA.</p>

            <h3 class="text-xl font-bold text-black mt-8 mb-2">Feedback</h3>
            <p>Agradecemos seus comentários sobre a acessibilidade da Minha Loja. Por favor, deixe-nos saber se você encontrar barreiras de acessibilidade em nosso site:</p>
            <ul class="list-disc pl-5 space-y-2 mt-2">
                <li>E-mail: acessibilidade@minhaloja.com.br</li>
                <li>Telefone: (11) 99999-9999</li>
            </ul>
            <p class="mt-4">Tentamos responder ao feedback dentro de 2 dias úteis.</p>
        '
    ]);
})->name('pages.accessibility');

// Rota: Dúvidas Gerais (FAQ)
Route::get('/duvidas-gerais', function () {
    // Definindo os dados da FAQ (Tópicos, Perguntas e Respostas)
    $faqTopics = [
        [
            'title' => 'Entregas',
            'slug' => 'entregas',
            'questions' => [
                [
                    'question' => 'Qual é o prazo de entrega?',
                    'answer' => 'O prazo de entrega varia de acordo com a sua região. Geralmente, para capitais, o prazo é de <strong>3 a 5 dias úteis</strong>. Para o interior, pode variar de 5 a 10 dias úteis.'
                ],
                [
                    'question' => 'Como rastrear meu pedido?',
                    'answer' => 'Assim que seu pedido for enviado, você receberá um código de rastreamento por e-mail. Você pode usar esse código no site da transportadora ou na sua área de cliente aqui no site.'
                ],
                [
                    'question' => 'Vocês entregam em todo o Brasil?',
                    'answer' => 'Sim! Enviamos para todo o território nacional através dos Correios e transportadoras parceiras.'
                ]
            ]
        ],
        [
            'title' => 'Pagamentos',
            'slug' => 'pagamentos',
            'questions' => [
                [
                    'question' => 'Quais são as formas de pagamento?',
                    'answer' => 'Aceitamos cartão de crédito (em até 12x), boleto bancário e PIX com 5% de desconto.'
                ],
                [
                    'question' => 'É seguro digitar meu cartão no site?',
                    'answer' => 'Totalmente. Utilizamos criptografia SSL de ponta a ponta e não armazenamos os dados completos do seu cartão. O processamento é feito diretamente pela operadora de pagamentos.'
                ]
            ]
        ],
        [
            'title' => 'Trocas e Devoluções',
            'slug' => 'trocas',
            'questions' => [
                [
                    'question' => 'Como faço para devolver um produto?',
                    'answer' => 'Você tem até 7 dias corridos após o recebimento para solicitar a devolução por arrependimento. Basta entrar em contato com nosso suporte.'
                ],
                [
                    'question' => 'A troca tem custo?',
                    'answer' => 'A primeira troca é por nossa conta! Geramos um código de postagem para você enviar o produto sem custo algum.'
                ]
            ]
        ]
    ];

    return view('pages.faq-page', compact('faqTopics'));
})->name('pages.faq');

// --- PÁGINAS DE CONTATO ---

// 1. Exibir o Formulário
Route::get('/fale-conosco', function () {
    return view('pages.contact');
})->name('pages.contact');

// 2. Processar o Envio (Recebe os dados, valida e envia o e-mail)
Route::post('/fale-conosco', function (Request $request) {
    // Validação dos dados
    $validated = $request->validate([
        'name' => 'required|min:3',
        'email' => 'required|email',
        'subject' => 'required',
        'order_number' => 'nullable|string',
        'message' => 'required|min:10',
    ], [
        // Mensagens personalizadas (opcional)
        'required' => 'Este campo é obrigatório.',
        'email' => 'Digite um e-mail válido.',
        'min' => 'O texto está muito curto.',
    ]);

    // Enviar o e-mail para a LOJA (simulado no log por enquanto)
    // Substitua 'admin@suaempresa.com' pelo e-mail real da loja
    Mail::to('admin@minhaloja.com.br')->send(new ContactMail($validated));

    // Redirecionar de volta com mensagem de sucesso
    return back()->with('success', 'Sua mensagem foi enviada com sucesso! Responderemos em breve.');

})->name('pages.contact.send');

    // oferta //

    Route::get('/offers', [ShopController::class, 'offers'])->name('shop.offers');

    // --- ROTAS DO CARRINHO (Abertas para todos) ---
    Route::get('/carrinho', [CartController::class, 'index'])->name('cart.index');
    Route::post('/carrinho/adicionar/{id}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/carrinho/remover/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::put('/carrinho/update/{id}', [CartController::class, 'update'])->name('cart.update');

// Rotas de Autenticação Store
Route::post('/login-check', [StoreAuthController::class, 'login'])->name('store.login');
Route::post('/login-verify', [StoreAuthController::class, 'verifyTwoFactor'])->name('store.verify');
Route::post('/register', [StoreAuthController::class, 'register'])->name('store.register');
Route::post('/logout', [StoreAuthController::class, 'logout'])->name('store.logout');

// Google
Route::get('/auth/google', [StoreAuthController::class, 'redirectToGoogle'])->name('login.google');
Route::get('/auth/google/callback', [StoreAuthController::class, 'handleGoogleCallback']);

// --- Rotas de Recuperação de Senha ---
Route::post('/forgot-password', [StoreAuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [StoreAuthController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [StoreAuthController::class, 'resetPassword'])->name('password.update');

// ---  PAINEL DO CLIENTE (Protegido por Login) ---
Route::middleware(['auth'])->prefix('minha-conta')->group(function () {
    
    // Painel Principal (Dados Pessoais)
    Route::get('/', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/update', [ProfileController::class, 'update'])->name('profile.update');

    // Pedidos
    Route::get('/pedidos', [ProfileController::class, 'orders'])->name('profile.orders');
    
    // Endereços
    Route::get('/enderecos', [ProfileController::class, 'addresses'])->name('profile.addresses');
    Route::post('/enderecos', [ProfileController::class, 'storeAddress'])->name('profile.address.store');
    Route::delete('/enderecos/{id}', [ProfileController::class, 'destroyAddress'])->name('profile.address.delete');

});

// --- Observação ---
// Removi as rotas de 'dashboard', 'profile' e o 'require auth.php' 
// pois elas dependem de pacotes de autenticação (Breeze/Jetstream) 
// que não estão instalados no seu projeto atual.