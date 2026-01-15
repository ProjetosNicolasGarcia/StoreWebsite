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

class StoreAuthController extends Controller
{
    // --- LOGIN NORMAL COM 2FA ---
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Validar Credenciais Básicas
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // 2. Gerar e Enviar 2FA
        $user->generateTwoFactorCode();
        
        // Tenta enviar e-mail, se falhar, loga erro mas não para o fluxo
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user->two_factor_code));
        } catch (\Exception $e) {
            \Log::error("Erro ao enviar email 2FA: " . $e->getMessage());
        }

        return response()->json([
            'status' => '2fa_required',
            'message' => 'Código enviado para seu e-mail.'
        ]);
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|integer',
        ]);

        $user = User::where('email', $request->email)
                    ->where('two_factor_code', $request->code)
                    ->where('two_factor_expires_at', '>', now())
                    ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => ['O código de verificação é inválido ou expirou.'],
            ]);
        }

        $user->resetTwoFactorCode();
        Auth::login($user);
        $request->session()->regenerate();

        // --- ALTERAÇÃO AQUI ---
        // Retorna a URL de redirecionamento para o JS processar
        return response()->json([
            'status' => 'success',
            'redirect_url' => route('profile.index')
        ]);
    }

    // --- CADASTRO ---
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

        // Gera código e pede 2FA (Fluxo mantido)
        $user->generateTwoFactorCode();
        
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user->two_factor_code));
        } catch (\Exception $e) {
            \Log::error("Erro ao enviar email 2FA: " . $e->getMessage());
        }

        return response()->json([
            'status' => '2fa_required',
            'message' => 'Conta criada! Verifique seu e-mail.'
        ]);
    }

    // --- LOGOUT ---
    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // --- GOOGLE LOGIN ---
   public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar
                ]);
            } else {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(uniqid()),
                ]);
            }

            Auth::login($user);
            
            // --- ALTERAÇÃO AQUI ---
            // Redireciona para o Painel do Usuário em vez da Home
            return redirect()->route('profile.index');

        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Erro ao logar com Google.');
        }
    }

    // --- RECUPERAÇÃO DE SENHA (MANTIDO IGUAL) ---
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

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

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Validação de segurança extra
        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Sua nova senha não pode ser igual à senha atual.'],
            ]);
        }

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