<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect; 
use Illuminate\Validation\Rule;
use App\Models\Address;

/**
 * Controller responsável pela Área do Cliente (Minha Conta).
 * OTIMIZADO: Implementa paginação e Eager Loading para suportar histórico extenso.
 */
class ProfileController extends Controller
{
    /**
     * Exibe o painel principal com os dados cadastrais do usuário.
     */
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    /**
     * Atualiza os dados pessoais e credenciais.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Regex Definições
        $phoneRegex = '/^\(?[1-9]{2}\)?\s?(?:9)[0-9]{4}\-?[0-9]{4}$/';
        $cpfRegex = '/^\d{3}\.\d{3}\.\d{3}\-\d{2}$/';
        
        // [NOVO] Regex para E-mail (padrão comum web: nome@dominio.com)
        $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

        // 1. Definição das regras base
        $rules = [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:20', 'regex:'.$phoneRegex],
            'cpf' => ['nullable', 'string', 'regex:'.$cpfRegex, Rule::unique('users')->ignore($user->id)],
            
            // [ALTERAÇÃO] Aplicação de Regex para E-mail
          'email' => ['required', 'email:rfc,dns', Rule::unique('users')->ignore($user->id), 'regex:'.$emailRegex],
            
            'birth_date' => 'required|date|before_or_equal:-18 years',

            'password' => [
                'nullable',
                'confirmed',
                'min:8',             
                'regex:/[a-z]/',     
                'regex:/[A-Z]/',     
                'regex:/[0-9]/',     
                'regex:/[\W_]/',     
                function ($attribute, $value, $fail) use ($user) {
                    if (Hash::check($value, $user->password)) {
                        $fail('A nova senha não pode ser igual à sua senha atual.');
                    }
                },
            ],
        ];

        // 2. Detecção de Alterações Sensíveis
        $emailChanged = $request->email !== $user->email;
        $passwordChanged = $request->filled('password');
        $phoneChanged = $request->phone !== $user->phone;
        $cpfChanged = $request->cpf !== $user->cpf;
        
        $inputDate = $request->birth_date;
        $userDate = $user->birth_date ? $user->birth_date->format('Y-m-d') : null;
        $birthDateChanged = $inputDate !== $userDate;

        // 3. Aplicação da Regra de Segurança
        if ($emailChanged || $passwordChanged || $phoneChanged || $cpfChanged || $birthDateChanged) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        // 4. Execução da Validação com MENSAGEM EDUCATIVA
        $validated = $request->validate($rules, [
            // [NOVO] Mensagens de E-mail
            'email.regex' => 'O formato do e-mail é inválido. Certifique-se de usar "nome@dominio.com".',
            'email.email' => 'Por favor, insira um endereço de e-mail válido.',
            'email.unique' => 'Este endereço de e-mail já está sendo usado por outro cliente.',

            // Mensagens de Formato
            'cpf.regex' => 'O CPF informado está incompleto ou inválido. Use o formato: 000.000.000-00.',
            'phone.regex' => 'O formato do telefone está incorreto. Use: (DD) 90000-0000.',
            
            // Segurança e Outros
            'current_password.required' => 'Por segurança, confirme sua senha atual para salvar alterações em dados sensíveis.',
            'current_password.current_password' => 'A senha atual digitada está incorreta.',
            'birth_date.required' => 'A data de nascimento é obrigatória.',
            'birth_date.before_or_equal' => 'É necessário ser maior de 18 anos para manter a conta.',
            'cpf.unique' => 'Este CPF já está vinculado a outra conta.',
            'password.min' => 'A senha é muito curta. Ela deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.regex' => 'A senha deve conter: letra maiúscula, letra minúscula, número e símbolo especial (ex: @, #, !).',
        ]);

        // 5. Persistência dos Dados
        $user->name = $validated['name'];
        $user->last_name = $validated['last_name'];
        $user->phone = $validated['phone'];
        $user->cpf = $validated['cpf'];
        $user->email = $validated['email'];
        $user->birth_date = $validated['birth_date'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Dados atualizados com sucesso!');
    }

    /**
     * Lista o histórico de pedidos do cliente.
     */
    public function orders()
    {
        $user = Auth::user();

        $orders = $user->orders()
            ->with(['items.product' => function($query) {
                $query->select('id', 'name', 'slug', 'image_url');
            }])
            ->latest()
            ->paginate(10);

        return view('profile.orders', compact('orders'));
    }

    /**
     * Lista os endereços cadastrados.
     */
    public function addresses()
    {
        $addresses = Auth::user()->addresses()->get();
        return view('profile.addresses', compact('addresses'));
    }

    /**
     * Salva um novo endereço vinculado ao usuário logado.
     */
    public function storeAddress(Request $request)
    {
        $validated = $request->validate([
            'zip_code' => 'required|string|max:9',
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:20',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:2',
        ]);

        $request->user()->addresses()->create($validated);

        return back()->with('success', 'Endereço adicionado com sucesso!');
    }

    /**
     * Remove um endereço.
     */
    public function destroyAddress($id)
    {
        Auth::user()->addresses()->findOrFail($id)->delete();

        return back()->with('success', 'Endereço removido.');
    }

    /**
     * Exclui (Desativa) a conta do usuário atual.
     */
    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ], [
            'password.current_password' => 'A senha digitada está incorreta.',
            'password.required' => 'Por favor, digite sua senha para confirmar.',
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->delete()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/')->with('success', 'Sua conta foi desativada com sucesso.');
        }

        return Redirect::back()->with('error', 'Houve um erro ao tentar desativar sua conta.');
    }
}