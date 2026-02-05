<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        // Se não estiver logado, segue vida (o middleware 'auth' cuida disso depois)
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Verifica se falta CPF ou Telefone
        if (empty($user->cpf) || empty($user->phone)) {
            
            // LISTA DE ROTAS PERMITIDAS (Para evitar loop infinito)
            // O usuário precisa poder acessar:
            // 1. A página de completar perfil
            // 2. A ação de salvar o perfil
            // 3. O logout (caso ele queira sair)
            $allowedRoutes = [
                'auth.complete-profile',
                'auth.update-profile',
                'logout',
            ];

            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return redirect()->route('auth.complete-profile');
            }
        }

        return $next($request);
    }
}