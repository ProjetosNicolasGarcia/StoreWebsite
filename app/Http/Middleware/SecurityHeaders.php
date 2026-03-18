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
            
            // Enforces HTTPS
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            
            // Basic CSP
            $response->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net; font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net; img-src 'self' data: https:; form-action 'self'; frame-ancestors 'self';");

            // ZAP FIX: Re-examine Cache-control Directives
            // Prevents the browser from caching sensitive dynamic pages (like /minha-conta)
            // Note: Static assets (CSS/JS/Images) handled by Apache/Nginx will still cache normally
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