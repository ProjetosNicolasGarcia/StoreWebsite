<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // 1. Definição das regras base
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'cpf' => ['nullable', 'string', 'max:14', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            // [ATUALIZAÇÃO DE SEGURANÇA]
            // Adicionadas regras de Regex para forçar a complexidade
            'password' => [
                'nullable',
                'confirmed',
                'min:8',             // Mínimo 8 caracteres
                'regex:/[a-z]/',     // Pelo menos uma letra minúscula
                'regex:/[A-Z]/',     // Pelo menos uma letra maiúscula
                'regex:/[0-9]/',     // Pelo menos um número
                'regex:/[\W_]/',     // Pelo menos um símbolo (caractere especial)
                
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

        // 3. Aplicação da Regra de Segurança
        if ($emailChanged || $passwordChanged || $phoneChanged || $cpfChanged) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        // 4. Execução da Validação com MENSAGEM EDUCATIVA
        $validated = $request->validate($rules, [
            // Mensagens de Segurança
            'current_password.required' => 'Por segurança, confirme sua senha atual para salvar as alterações.',
            'current_password.current_password' => 'A senha atual digitada está incorreta.',

            // [MENSAGEM MELHORADA]
            // Se falhar no tamanho (min:8)
            'password.min' => 'A senha é muito curta. Ela deve ter no mínimo 8 caracteres.',
            
            // Se falhar na confirmação
            'password.confirmed' => 'A confirmação da senha não confere.',

            // Se falhar em QUALQUER regra de complexidade (regex)
            // Essa mensagem ensina ao usuário o padrão correto imediatamente
            'password.regex' => 'A senha deve conter: letra maiúscula, letra minúscula, número e símbolo especial (ex: @, #, !).',
            
            // Outras mensagens
            'email.unique' => 'Este endereço de e-mail já está sendo usado por outro cliente.',
            'cpf.unique' => 'Este CPF já está vinculado a outra conta.',
        ]);

        // 5. Persistência dos Dados
        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->cpf = $validated['cpf'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Dados atualizados com sucesso!');
    }
    /**
     * Lista o histórico de pedidos do cliente.
     * [OTIMIZAÇÃO DE PERFORMANCE E ESCALABILIDADE]
     */
    public function orders()
    {
        $user = Auth::user();

        // Alteração Vital:
        // 1. paginate(10): Evita carregar 1000 pedidos na memória se o cliente for antigo.
        // 2. with('items.product'): Previne o problema N+1.
        //    Carrega os Itens do pedido E os Produtos desses itens em apenas 2 queries adicionais.
        //    (Assumindo que na view você mostra "Camiseta Azul (x2)")
        $orders = $user->orders()
            ->with(['items.product' => function($query) {
                // Seleciona apenas campos essenciais do produto para economizar memória
                $query->select('id', 'name', 'slug', 'image_url');
            }])
            ->latest() // Atalho para orderBy('created_at', 'desc')
            ->paginate(10); // Paginação é obrigatória para escalabilidade

        return view('profile.orders', compact('orders'));
    }

    /**
     * Lista os endereços cadastrados.
     */
    public function addresses()
    {
        // Carregamento simples, mas explícito
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
        // Proteção IDOR mantida: Só deleta se pertencer ao usuário logado
        Auth::user()->addresses()->findOrFail($id)->delete();

        return back()->with('success', 'Endereço removido.');
    }
}