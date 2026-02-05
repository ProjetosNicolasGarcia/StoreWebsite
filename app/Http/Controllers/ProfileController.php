<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Address;

/**
 * Controller responsável pela Área do Cliente (Minha Conta).
 * Gerencia dados pessoais, segurança (senha), histórico de pedidos e catálogo de endereços.
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
     * * Lógica de Segurança:
     * Implementa uma validação condicional ("Re-authentication").
     * Se o usuário tentar alterar dados sensíveis (Email, CPF, Senha),
     * o sistema exige a confirmação da senha atual.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // 1. Definição das regras base
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            // Valida CPF único, ignorando o próprio usuário (para não dar erro ao salvar o mesmo CPF)
            'cpf' => ['nullable', 'string', 'max:14', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:8|confirmed',
        ];

        // 2. Detecção de Alterações Sensíveis
        // Compara o input do formulário com o banco de dados
        $emailChanged = $request->email !== $user->email;
        $passwordChanged = $request->filled('password'); // Se o campo senha foi preenchido
        $phoneChanged = $request->phone !== $user->phone;
        $cpfChanged = $request->cpf !== $user->cpf;

        // 3. Aplicação da Regra de Segurança
        // Se mudou algo crítico, injeta a obrigatoriedade da 'current_password'
        if ($emailChanged || $passwordChanged || $phoneChanged || $cpfChanged) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        // 4. Execução da Validação
        $validated = $request->validate($rules, [
            'current_password.required' => 'Para alterar dados sensíveis (E-mail, CPF, Telefone ou Senha), confirme sua senha atual.',
            'current_password.current_password' => 'A senha atual digitada está incorreta.',
        ]);

        // 5. Persistência dos Dados
        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->cpf = $validated['cpf'];
        $user->email = $validated['email'];

        // Só altera a senha (e faz o hash) se o usuário digitou uma nova
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
        // Ordena por 'created_at desc' para mostrar os pedidos mais recentes primeiro
        $orders = Auth::user()->orders()->orderBy('created_at', 'desc')->get();
        return view('profile.orders', compact('orders'));
    }

    /**
     * Lista os endereços cadastrados.
     */
    public function addresses()
    {
        $addresses = Auth::user()->addresses;
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

        // Cria o endereço através do relacionamento para garantir o vínculo automático com o User ID
        $request->user()->addresses()->create($validated);

        return back()->with('success', 'Endereço adicionado com sucesso!');
    }

    /**
     * Remove um endereço.
     */
    public function destroyAddress($id)
    {
        // SEGURANÇA (IDOR Protection):
        // Busca o endereço SOMENTE dentro da coleção do usuário logado ($request->user()->addresses()).
        // Se o ID existir no banco mas for de outro usuário, o findOrFail lança 404, impedindo a exclusão.
        Auth::user()->addresses()->findOrFail($id)->delete();

        return back()->with('success', 'Endereço removido.');
    }
}