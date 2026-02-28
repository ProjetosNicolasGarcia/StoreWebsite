<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password; 
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;

class StoreAuthController extends Controller
{
    // =========================================================================
    // VIEWS
    // =========================================================================

    public function showLoginForm()
    {
        // O sistema usa o componente Alpine (auth-slider) na página inicial.
        // Se baterem na rota /login, redirecionamos em segurança para a home.
        return redirect('/');
    }

    public function showRegisterForm()
    {
        // O mesmo para a rota /register
        return redirect('/');
    }

    public function showTwoFactorForm()
    {
        if (!Session::has('auth.2fa.id') && !Session::has('auth.registration_email')) {
            return redirect('/'); // Alterado de route('login') para '/'
        }
        return view('auth.two-factor');
    }

    // =========================================================================
    // LOGIN
    // =========================================================================

    public function login(Request $request)
    {
        $messages = [
            'email.required' => 'Por favor, digite o seu e-mail.',
            'email.email' => 'O e-mail informado não é válido.',
            'password.required' => 'Por favor, digite a sua senha.',
        ];

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], $messages);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha incorretos. Verifique e tente novamente.'],
            ]);
        }

        $user->generateTwoFactorCode();
        
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user->two_factor_code));
        } catch (\Exception $e) {
            \Log::error("Erro login email 2FA: " . $e->getMessage());
        }

        Session::put('auth.2fa.id', $user->id);
        Session::forget('auth.registration_email');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => '2fa_required',
                'message' => 'Código de verificação enviado.'
            ]);
        }

        return redirect()->route('auth.two-factor');
    }

    // =========================================================================
    // REGISTRO
    // =========================================================================

  public function register(Request $request)
    {
        $passwordRules = PasswordRules::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();

        $phoneRegex = '/^\(?[1-9]{2}\)?\s?(?:9)[0-9]{4}\-?[0-9]{4}$/';
        $cpfRegex = '/^\d{3}\.\d{3}\.\d{3}\-\d{2}$/';
        $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

        $messages = [
            'required' => 'Este campo é obrigatório.',
            'name.required' => 'Precisamos do seu nome para criar a conta.',
            'last_name.required' => 'O sobrenome é obrigatório para processamento de pagamentos.',
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'Por favor, insira um endereço de e-mail válido.',
            'email.unique' => 'Este e-mail já está sendo usado por outra conta.',
            'email.regex' => 'O formato do e-mail é inválido. Certifique-se de usar "nome@dominio.com".',
            'cpf.unique' => 'Este CPF já possui uma conta cadastrada.',
            'cpf.regex' => 'O CPF deve estar no formato 000.000.000-00 e conter 11 dígitos.',
            'phone.regex' => 'O telefone deve incluir DDD e começar com 9. Ex: (11) 99999-9999.',
            'phone.unique' => 'Este número de telefone já está cadastrado.',
            'password.required' => 'Crie uma senha para sua segurança.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'birth_date.required' => 'A data de nascimento é obrigatória.',
            'birth_date.before_or_equal' => 'Você precisa ter pelo menos 18 anos para criar uma conta.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255', // NOVO CAMPO
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users', 'regex:'.$emailRegex],
            'password' => ['required', 'confirmed', $passwordRules],
            'cpf' => ['nullable', 'string', 'regex:' . $cpfRegex, 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users', 'regex:' . $phoneRegex],
            'birth_date' => 'required|date|before_or_equal:-18 years',
        ], $messages);

        $code = rand(100000, 999999);

        $registrationData = [
            'name' => $validated['name'],
            'last_name' => $validated['last_name'], // NOVO CAMPO
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'cpf' => $validated['cpf'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'birth_date' => $validated['birth_date'],
            'code' => $code
        ];

        Cache::put('registration_' . $validated['email'], $registrationData, now()->addMinutes(10));

        try {
            Mail::to($validated['email'])->send(new TwoFactorCodeMail($code));
        } catch (\Exception $e) {
            \Log::error("Erro registro email 2FA: " . $e->getMessage());
        }

        Session::put('auth.registration_email', $validated['email']);
        Session::forget('auth.2fa.id');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => '2fa_required',
                'message' => 'Tudo certo! Verifique o código enviado ao seu e-mail.'
            ]);
        }

        return redirect()->route('auth.two-factor');
    }

    // =========================================================================
    // VERIFICAÇÃO CÓDIGO
    // =========================================================================

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|integer',
        ], [
            'two_factor_code.required' => 'Por favor, digite o código de 6 números.',
            'two_factor_code.integer' => 'O código deve conter apenas números.',
        ]);

        if (Session::has('auth.2fa.id')) {
            return $this->verifyLogin($request);
        }

        if (Session::has('auth.registration_email')) {
            return $this->verifyRegistration($request);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'A sessão expirou. Recomece o login.'], 419);
        }
        return redirect()->route('login');
    }

    protected function verifyLogin(Request $request)
    {
        $user = User::find(Session::get('auth.2fa.id'));

        if (!$user || !$user->hasValidTwoFactorCode($request->two_factor_code)) {
            throw ValidationException::withMessages([
                'two_factor_code' => ['O código informado está incorreto ou expirou.'],
            ]);
        }

        $user->resetTwoFactorCode();
        Session::forget('auth.2fa.id');
        Auth::login($user);
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'redirect_url' => route('profile.index')]);
        }
        return redirect()->intended(route('profile.index'));
    }

    protected function verifyRegistration(Request $request)
    {
        $email = Session::get('auth.registration_email');
        $cachedData = Cache::get('registration_' . $email);

        if (!$cachedData) {
            throw ValidationException::withMessages([
                'two_factor_code' => ['O tempo limite acabou. Por favor, faça o cadastro novamente.'],
            ]);
        }

        if ($cachedData['code'] != $request->two_factor_code) {
            throw ValidationException::withMessages([
                'two_factor_code' => ['Código incorreto. Verifique seu e-mail.'],
            ]);
        }

        if (User::where('email', $cachedData['email'])->exists()) {
             throw ValidationException::withMessages(['email' => ['Este e-mail acabou de ser registrado por outra pessoa.']]);
        }

        unset($cachedData['code']);
        $user = User::create($cachedData);

        Cache::forget('registration_' . $email);
        Session::forget('auth.registration_email');

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'redirect_url' => route('profile.index')]);
        }
        return redirect()->route('profile.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // =========================================================================
    // GOOGLE SOCIALITE
    // =========================================================================
    
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

  public function handleGoogleCallback()
    {
        try {
            // Stateless é importante para localhost/127.0.0.1
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('google_id', $googleUser->id)->orWhere('email', $googleUser->email)->first();

            // Acessamos o array bruto ('user') retornado pela API do Google
            // O Google já separa inteligentemente o given_name (Nome) e o family_name (Sobrenome)
            $firstName = $googleUser->user['given_name'] ?? $googleUser->name;
            $lastName = $googleUser->user['family_name'] ?? '';

            if ($user) {
                // Atualiza os tokens do Google, mas NÃO sobrescreve o nome e sobrenome
                // Isso respeita caso o cliente tenha editado os próprios dados na aba "Meus Dados"
                $user->update([
                    'google_id' => $googleUser->id, 
                    'avatar' => $googleUser->avatar
                ]);
            } else {
                // Criação de nova conta com os dados exatos do Google
                $user = User::create([
                    'name' => $firstName, 
                    'last_name' => $lastName, // Agora recebe o family_name exato
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id, 
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(uniqid()),
                ]);
            }
            Auth::login($user);
            return redirect()->route('profile.index');
        } catch (\Exception $e) {
            return redirect('/')->withErrors(['email' => 'Não foi possível entrar com o Google.']);
        }
    }

    // =========================================================================
    // RECUPERAÇÃO DE SENHA
    // =========================================================================

    protected function getPasswordStatusMessage($status)
    {
        $messages = [
            Password::RESET_LINK_SENT => 'Link de redefinição enviado para o seu e-mail!',
            Password::PASSWORD_RESET => 'Sua senha foi redefinida com sucesso!',
            Password::INVALID_USER => 'Não encontramos um usuário com esse e-mail.',
            Password::INVALID_TOKEN => 'O link de redefinição é inválido ou expirou.',
            'passwords.throttled' => 'Muitas tentativas. Por favor, aguarde antes de tentar novamente.',
        ];

        return $messages[$status] ?? 'Ocorreu um erro desconhecido.';
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email'], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'E-mail inválido.',
        ]);
        
        $status = Password::sendResetLink($request->only('email'));
        $message = $this->getPasswordStatusMessage($status);
        
        if ($request->wantsJson()) {
             return $status === Password::RESET_LINK_SENT
                ? response()->json(['message' => $message])
                : response()->json(['message' => $message], 422);
        }
        return $status === Password::RESET_LINK_SENT 
            ? back()->with('status', $message) 
            : back()->withErrors(['email' => $message]);
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with(['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $passwordRules = PasswordRules::min(8)->letters()->mixedCase()->numbers()->symbols();

        $messages = [
            'password.confirmed' => 'A confirmação de senha não confere.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.letters' => 'A senha deve conter pelo menos uma letra.',
            'password.mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
            'password.numbers' => 'Adicione um número à senha.',
            'password.symbols' => 'Adicione um símbolo à senha (@, #, $).',
        ];

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', $passwordRules],
        ], $messages);

        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['password' => ['Por segurança, a nova senha não pode ser igual à antiga.']]);
        }
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        
        $message = $this->getPasswordStatusMessage($status);

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('home')->with('status', $message);
        }
        return back()->withInput($request->only('email'))->withErrors(['email' => $message]);
    }

    // =========================================================================
    // COMPLETAR PERFIL (GOOGLE/SOCIALITE)
    // =========================================================================

    public function showCompleteProfile()
    {
        return view('auth.complete-profile');
    }

    public function updateProfile(Request $request)
    {
        $phoneRegex = '/^\(?[1-9]{2}\)?\s?(?:9)[0-9]{4}\-?[0-9]{4}$/';
        $cpfRegex = '/^\d{3}\.\d{3}\.\d{3}\-\d{2}$/';

        $messages = [
            'cpf.required' => 'O CPF é obrigatório para emissão de nota fiscal.',
            'cpf.unique' => 'Este CPF já está em uso.',
            'cpf.regex' => 'O CPF deve estar no formato 000.000.000-00.',
            'phone.required' => 'O telefone é obrigatório para contato de entrega.',
            'phone.regex' => 'Formato de telefone inválido. Use (DD) 90000-0000.',
            'birth_date.required' => 'A data de nascimento é obrigatória.',
            'birth_date.before_or_equal' => 'É necessário ter pelo menos 18 anos.',
        ];

        $request->validate([
            'cpf' => ['required', 'string', 'regex:' . $cpfRegex, 'unique:users,cpf,' . Auth::id()],
            'phone' => ['required', 'string', 'max:20', 'regex:'.$phoneRegex],
            'birth_date' => 'required|date|before_or_equal:-18 years',
        ], $messages);

        $user = Auth::user(); 
        
        $user->forceFill([
            'cpf' => $request->cpf,
            'phone' => $request->phone,
            'birth_date' => $request->birth_date,
        ])->save();

        return redirect()->route('profile.index')->with('status', 'Perfil completado com sucesso!');
    }
}