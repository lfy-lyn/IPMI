<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed divisions
        Division::create(['name' => 'Kaligrafi', 'status' => 'active']);
        Division::create(['name' => 'MTQ', 'status' => 'active']);
        Division::create(['name' => 'Dakwah', 'status' => 'active']);
        Division::create(['name' => 'Hadroh', 'status' => 'active']);
    }

    public function test_login_page_loads()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_authenticated_user_redirected_from_login()
    {
        $user = User::factory()->create(['status' => 'active']);
        
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/dashboard');
    }

    public function test_login_with_valid_credentials()
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_invalid_password()
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_with_inactive_account()
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_with_pending_account()
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create(['status' => 'active']);
        
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_logout_functionality()
    {
        $user = User::factory()->create(['status' => 'active']);
        
        $this->actingAs($user);
        $response = $this->post('/logout');
        
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_remember_me_functionality()
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }
}
