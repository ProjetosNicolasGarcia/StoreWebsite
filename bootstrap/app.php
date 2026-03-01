<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureProfileIsComplete;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Adicione aqui para rodar em todas as rotas web
        $middleware->web(append: [
            EnsureProfileIsComplete::class,
        ]);

        // EXCEÇÃO DO CSRF PARA O WEBHOOK DO MERCADO PAGO AQUI
        $middleware->validateCsrfTokens(except: [
            'webhooks/mercadopago',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();