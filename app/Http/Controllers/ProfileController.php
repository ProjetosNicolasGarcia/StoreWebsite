<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Address;

class ProfileController extends Controller
{
    // 1. Painel Principal (Dados da Conta)
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    // 2. Atualizar Dados (Com verificação de segurança)
    public function update(Request $request)
    {
        $user = Auth::user();

        // 1. Define as regras básicas de validação
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'cpf' => ['nullable', 'string', 'max:14', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:8|confirmed',
        ];

        // 2. Verifica se houve alteração em dados sensíveis
        // Compara o valor que veio do form ($request) com o do banco ($user)
        $emailChanged = $request->email !== $user->email;
        $passwordChanged = $request->filled('password'); // Se preencheu senha nova
        $phoneChanged = $request->phone !== $user->phone;
        $cpfChanged = $request->cpf !== $user->cpf;

        // 3. Se mudou algo crítico, adiciona a regra que EXIGE a senha atual
        if ($emailChanged || $passwordChanged || $phoneChanged || $cpfChanged) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        // 4. Valida tudo de uma vez
        $validated = $request->validate($rules, [
            'current_password.required' => 'Para alterar dados sensíveis (E-mail, CPF, Telefone ou Senha), confirme sua senha atual.',
            'current_password.current_password' => 'A senha atual digitada está incorreta.',
        ]);

        // 5. Atualiza os dados no objeto do usuário
        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->cpf = $validated['cpf'];
        $user->email = $validated['email'];

        // Só faz o hash da senha se ela foi preenchida
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Dados atualizados com sucesso!');
    }

    // 3. Histórico de Pedidos
    public function orders()
    {
        $orders = Auth::user()->orders()->orderBy('created_at', 'desc')->get();
        return view('profile.orders', compact('orders'));
    }

    // 4. Meus Endereços (Listagem)
    public function addresses()
    {
        $addresses = Auth::user()->addresses;
        return view('profile.addresses', compact('addresses'));
    }

    // 5. Salvar Novo Endereço
    public function storeAddress(Request $request)
    {
        $validated = $request->validate([
            'zip_code' => 'required|string|max:9',
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:20',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'required|string|max:255', // Garante o uso de neighborhood
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:2',
        ]);

        $request->user()->addresses()->create($validated);

        return back()->with('success', 'Endereço adicionado com sucesso!');
    }

    // 6. Excluir Endereço
    public function destroyAddress($id)
    {
        Auth::user()->addresses()->findOrFail($id)->delete();
        return back()->with('success', 'Endereço removido.');
    }
}