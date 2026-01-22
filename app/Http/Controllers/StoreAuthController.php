<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * Controller de Autenticação da Loja (Área do Cliente).
 * Gerencia:
 * - Login com 2FA (Email com código de verificação).
 * - Cadastro de novos clientes.
 * - Login Social (Google via Socialite).
 * - Recuperação e Reset de Senha com regras de segurança extras.
 */
class StoreAuthController extends Controller
{
    /**
     * Inicia o processo de login.
     * Passo 1: Valida credenciais (email/senha).
     * Passo 2: Se correto, gera um código 2FA e envia por e-mail, sem logar o usuário imediatamente.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Validar Credenciais Básicas (Hash check)
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // 2. Gerar e Enviar Código 2FA
        // O login não é finalizado aqui. O front-end deve aguardar o input do código.
        $user->generateTwoFactorCode();
        
        // Tratamento de erro de envio de e-mail para não travar a aplicação (ex: falha no SMTP)
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user->two_factor_code));
        } catch (\Exception $e) {
            \Log::error("Erro crítico ao enviar email 2FA: " . $e->getMessage());
            // Em produção, considere avisar o usuário que houve um erro no envio.
        }

        // Retorna status para o Javascript exibir o modal de código
        return response()->json([
            'status' => '2fa_required',
            'message' => 'Código de segurança enviado para seu e-mail.'
        ]);
    }

    /**
     * Finaliza o login validando o código 2FA.
     * Recebe o código digitado pelo usuário e efetiva a sessão.
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|integer',
        ]);

        // Busca usuário combinando E-mail + Código + Validade do Código
        $user = User::where('email', $request->email)
                    ->where('two_factor_code', $request->code)
                    ->where('two_factor_expires_at', '>', now())
                    ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => ['O código de verificação é inválido ou expirou.'],
            ]);
        }

        // Limpa o código para evitar reuso
        $user->resetTwoFactorCode();
        
        // Autentica o usuário na sessão do Laravel
        Auth::login($user);
        $request->session()->regenerate(); // Proteção contra Session Fixation

        // Retorna a URL de destino para o front-end redirecionar
        return response()->json([
            'status' => 'success',
            'redirect_url' => route('profile.index')
        ]);
    }

    /**
     * Processa o cadastro de novos clientes.
     * Após criar a conta, força o fluxo de 2FA para validar o e-mail imediatamente.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'cpf' => 'nullable|string|max:14|unique:users',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'cpf' => $validated['cpf'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
        ]);

        // Inicia fluxo de 2FA para validar e-mail
        $user->generateTwoFactorCode();
        
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user->two_factor_code));
        } catch (\Exception $e) {
            \Log::error("Erro ao enviar email 2FA no cadastro: " . $e->getMessage());
        }

        return response()->json([
            'status' => '2fa_required',
            'message' => 'Conta criada com sucesso! Verifique seu e-mail para validar o acesso.'
        ]);
    }

    /**
     * Encerra a sessão do usuário.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // --- GOOGLE LOGIN (SOCIALITE) ---

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback do Google.
     * Cria o usuário ou vincula a uma conta existente baseada no e-mail.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Tenta encontrar usuário pelo ID do Google ou pelo E-mail
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            if ($user) {
                // Atualiza dados caso o usuário já exista (ex: mudou avatar no Google)
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar
                ]);
            } else {
                // Cria novo usuário com senha aleatória (já que o login é via Google)
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(uniqid()), // Senha dummy, inacessível via login normal sem reset
                ]);
            }

            Auth::login($user);
            
            // Redireciona diretamente para o painel do cliente
            return redirect()->route('profile.index');

        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Falha na autenticação com Google. Tente novamente.');
        }
    }

    // --- RECUPERAÇÃO DE SENHA ---

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Envia o link padrão do Laravel
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Processa o Reset de Senha.
     * Inclui validação de segurança para impedir reuso da senha atual.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // [SEGURANÇA] Verifica se a nova senha é igual à atual
        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Por segurança, sua nova senha não pode ser igual à senha atual.'],
            ]);
        }

        // Executa o reset padrão do Laravel
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('home')->with('status', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}