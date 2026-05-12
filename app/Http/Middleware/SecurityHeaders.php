<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ZAP FIX: Big Redirect Detected
        if ($response->isRedirection()) {
            $response->setContent('');
        }

        if (method_exists($response, 'header')) {
            // Prevents Clickjacking
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            
            // Prevents MIME-sniffing
            $response->header('X-Content-Type-Options', 'nosniff');

            // Enforces HTTPS (Apenas em Produção)
            if (app()->environment('production')) {
                $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            }
            
            // CSP Ajustado: 
            // 1. Incluído o domínio .com do Mercado Pago no script-src, connect-src e frame-src
            // 2. Removidos os domínios do VLibras, mantendo apenas a estrutura do UserWay
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://cdn.jsdelivr.net https://*.userway.org https://sdk.mercadopago.com https://*.mercadopago.com https://*.mercadopago.com.br https://*.mlstatic.com; " .
                   "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://*.userway.org; " .
                   "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data: https://*.userway.org; " .
                   "img-src 'self' data: blob: https: http:; " .
                   "connect-src 'self' https://*.userway.org https://cdn.jsdelivr.net wss: https://api.mercadopago.com https://*.mercadopago.com https://*.mercadopago.com.br https://*.mlstatic.com https://*.mercadolibre.com; " .
                   "frame-src 'self' https://*.userway.org https://*.mercadopago.com https://*.mercadopago.com.br https://*.mlstatic.com https://*.mercadolibre.com https://www.youtube.com; " .
                   "worker-src 'self' blob:; " .
                   "form-action 'self'; " .
                   "frame-ancestors 'self';";
            
            $response->header('Content-Security-Policy', $csp);

            // ZAP FIX: Re-examine Cache-control Directives
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');
        }

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}