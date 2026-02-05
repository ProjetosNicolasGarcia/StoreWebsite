<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Regex simples para CPF (XXXYYYZZZ-WW ou formatado). Idealmente usar uma regra de validação customizada (ex: laravel-legends/pt-br-validator)
            'cpf' => ['required', 'string', 'unique:users', 'regex:/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/'],
            'phone' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before:today'], // Ajustar 'before:-18 years' se for estrito
        ];
    }

    public function messages()
    {
        return [
            'cpf.regex' => 'O formato do CPF é inválido.',
            'birth_date.before' => 'A data de nascimento deve ser válida.',
        ];
    }
}