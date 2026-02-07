<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test; // <--- Importação necessária para o novo padrão

class AgeRestrictionTest extends TestCase
{
    use RefreshDatabase;

    #[Test] // <--- Substitui o /** @test */
    public function users_under_18_cannot_register()
    {
        $response = $this->post(route('register'), [
            'name' => 'Teste Menor',
            'email' => 'menor@teste.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'phone' => '(11) 99999-9999',
            'birth_date' => now()->subYears(15)->format('Y-m-d'), // 15 anos
        ]);

        $response->assertSessionHasErrors(['birth_date']);
    }

    #[Test]
    public function users_over_18_can_register()
    {
        // Mock do envio de email para não quebrar o teste e não depender de SMTP
        Mail::fake();

        $response = $this->post(route('register'), [
            'name' => 'Teste Maior',
            'email' => 'maior@teste.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'phone' => '(11) 99999-9999',
            'birth_date' => '2000-01-01', // +18 anos
        ]);

        $response->assertSessionHasNoErrors();
        // Verifica se foi redirecionado para o 2FA (comportamento padrão do seu controller)
        $response->assertRedirect(route('auth.two-factor'));
    }

    #[Test]
    public function user_cannot_update_profile_to_be_under_18()
    {
        $password = 'Password@123';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'birth_date' => '2000-01-01',
        ]);

        $this->actingAs($user);

        $response = $this->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'birth_date' => now()->subYears(10)->format('Y-m-d'), // Tentando mudar para 10 anos
            'current_password' => $password, // Senha necessária para alteração sensível
        ]);

        $response->assertSessionHasErrors(['birth_date']);
    }

    #[Test]
    public function update_birth_date_requires_current_password()
    {
        $user = User::factory()->create([
            'birth_date' => '1990-01-01',
        ]);

        $this->actingAs($user);

        // Tenta mudar a data SEM enviar a senha atual
        $response = $this->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'birth_date' => '1995-01-01', // Data diferente
            // 'current_password' => enviada vazia ou ausente
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }
}
