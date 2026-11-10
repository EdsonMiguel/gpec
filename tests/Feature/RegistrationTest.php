<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @return array{nome_fazenda: string, name: string, email: string, password: string, password_confirmation: string} */
    private function registrationData(): array
    {
        return [
            'nome_fazenda' => 'Fazenda Boa Vista',
            'name' => 'Maria Silva',
            'email' => 'maria@example.test',
            'password' => 'MinhaSenhaSegura123!',
            'password_confirmation' => 'MinhaSenhaSegura123!',
        ];
    }

    public function test_guest_can_open_registration_from_login(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('register'));
        $this->get('/cadastro')->assertOk()->assertSee('Criar minha conta')->assertSee(route('login'));
    }

    public function test_registration_creates_an_admin_in_a_new_isolated_tenant_and_logs_in(): void
    {
        $existingUser = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($existingUser);
        Animal::create([
            'tenant_id' => $existingUser->tenant_id, 'brinco' => 'EXCLUSIVO',
            'categoria' => 'BOI', 'sexo' => 'MACHO', 'status' => 'ATIVO',
        ]);
        Auth::logout();

        $this->post('/cadastro', [...$this->registrationData(), 'email' => ' MARIA@EXAMPLE.TEST '])
            ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

        $user = User::where('email', 'maria@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Admin', $user->role);
        $this->assertNotEquals($existingUser->tenant_id, $user->tenant_id);
        $this->assertSame('Fazenda Boa Vista', $user->tenant->nome);
        $this->assertTrue(Hash::check($this->registrationData()['password'], $user->password));
        $this->assertNull($user->email_verified_at);
        $this->get('/dashboard')->assertOk();
        $this->get('/animais')->assertOk()->assertDontSee('EXCLUSIVO');
        $this->get('/usuarios')->assertOk()->assertSee('Maria Silva')->assertDontSee($existingUser->email);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->post('/login', ['email' => $user->email, 'password' => $this->registrationData()['password']])
            ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    }

    public function test_invalid_registration_does_not_create_users_or_tenants(): void
    {
        $this->post('/cadastro', [
            'nome_fazenda' => '', 'name' => '', 'email' => 'invalid',
            'password' => 'short', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['nome_fazenda', 'name', 'email', 'password']);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_existing_email_is_rejected_without_creating_another_tenant(): void
    {
        User::factory()->create(['email' => 'maria@example.test']);
        $this->post('/cadastro', $this->registrationData())->assertSessionHasErrors('email');
        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_registration_cannot_select_an_existing_tenant_or_role(): void
    {
        $user = User::factory()->create();
        $this->post('/cadastro', [
            ...$this->registrationData(), 'tenant_id' => $user->tenant_id, 'role' => 'Vaqueiro',
        ])->assertSessionHasErrors(['tenant_id', 'role']);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('tenants', 1);
    }

    public function test_authenticated_users_cannot_register_another_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/cadastro')->assertRedirect(route('dashboard'));
        $this->post('/cadastro', $this->registrationData())->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('tenants', 1);
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/cadastro', [])->assertSessionHasErrors();
        }
        $this->post('/cadastro', $this->registrationData())->assertStatus(429);
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_user_creation_failure_rolls_back_the_tenant(): void
    {
        Event::listen('eloquent.creating: '.User::class, function (User $user): void {
            throw new RuntimeException('Falha simulada ao salvar usuário.');
        });
        $this->withoutExceptionHandling();

        try {
            $this->post('/cadastro', $this->registrationData());
            $this->fail('O cadastro deveria falhar.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada ao salvar usuário.', $exception->getMessage());
        } finally {
            Event::forget('eloquent.creating: '.User::class);
        }

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }
}
