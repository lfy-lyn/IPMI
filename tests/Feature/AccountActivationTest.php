<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use App\Models\MembershipApplication;
use App\Models\ActivationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountActivationMail;
use Tests\TestCase;

class AccountActivationTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;
    protected Position $ketuaUmum;
    protected User $kuUser;
    protected Member $kuMember;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->division = Division::create(['name' => 'Kaligrafi', 'status' => 'active']);
        $this->ketuaUmum = Position::create(['name' => 'Ketua Umum', 'status' => 'active']);
        
        $this->kuUser = User::create([
            'email' => 'ku@test.com', 
            'password' => Hash::make('password'), 
            'status' => 'active'
        ]);
        
        $this->kuMember = Member::create([
            'user_id' => $this->kuUser->id, 
            'nis' => '001', 
            'full_name' => 'Ketua Umum', 
            'division_id' => $this->division->id
        ]);
        
        $this->kuMember->positions()->attach($this->ketuaUmum);
    }

    public function test_approve_creates_user_and_member()
    {
        Mail::fake();

        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $this->actingAs($this->kuUser)->post(route('admin.membership.approve', $application));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('members', [
            'nis' => '12345',
            'full_name' => 'Test User',
            'division_id' => $this->division->id,
            'status' => 'active',
        ]);
    }

    public function test_approve_generates_activation_token()
    {
        Mail::fake();

        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $this->actingAs($this->kuUser)->post(route('admin.membership.approve', $application));

        $user = User::where('email', 'test@example.com')->first();
        $member = Member::where('user_id', $user->id)->first();

        $this->assertDatabaseHas('activation_tokens', [
            'member_id' => $member->id,
            'used_at' => null,
        ]);

        $token = ActivationToken::where('member_id', $member->id)->first();
        $this->assertNotNull($token->token);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isFuture());
    }

    public function test_approve_sends_activation_email()
    {
        Mail::fake();

        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $this->actingAs($this->kuUser)->post(route('admin.membership.approve', $application));

        Mail::assertSent(AccountActivationMail::class, function ($mail) use ($application) {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_approve_does_not_create_duplicate_user()
    {
        Mail::fake();

        // Create existing user
        $existingUser = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'status' => 'pending',
        ]);

        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $this->actingAs($this->kuUser)->post(route('admin.membership.approve', $application));

        $this->assertEquals(2, User::count()); // Only kuUser and existingUser, no duplicate
    }

    public function test_activation_page_shows_for_valid_token()
    {
        $user = User::create(['email' => 'test@example.com', 'password' => Hash::make('temp'), 'status' => 'pending']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '12345', 'full_name' => 'Test', 'division_id' => $this->division->id]);
        $token = ActivationToken::generate($member);

        $response = $this->get(route('activation.show', ['token' => $token->token]));

        $response->assertStatus(200);
        $response->assertSee('Aktivasi Akun');
        $response->assertSee('Test');
        $response->assertSee('test@example.com');
    }

    public function test_activation_page_shows_error_for_invalid_token()
    {
        $response = $this->get(route('activation.show', ['token' => 'invalid-token']));

        $response->assertStatus(200);
        $response->assertSee('Token aktivasi tidak valid');
    }

    public function test_activation_page_shows_error_for_expired_token()
    {
        $user = User::create(['email' => 'test@example.com', 'password' => Hash::make('temp'), 'status' => 'pending']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '12345', 'full_name' => 'Test', 'division_id' => $this->division->id]);
        $token = ActivationToken::create([
            'member_id' => $member->id,
            'token' => 'expired-token',
            'expires_at' => now()->subHours(1),
        ]);

        $response = $this->get(route('activation.show', ['token' => $token->token]));

        $response->assertStatus(200);
        $response->assertSee('Token aktivasi sudah kedaluwarsa');
    }

    public function test_activation_page_shows_error_for_used_token()
    {
        $user = User::create(['email' => 'test@example.com', 'password' => Hash::make('temp'), 'status' => 'pending']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '12345', 'full_name' => 'Test', 'division_id' => $this->division->id]);
        $token = ActivationToken::create([
            'member_id' => $member->id,
            'token' => 'used-token',
            'expires_at' => now()->addHours(48),
            'used_at' => now(),
        ]);

        $response = $this->get(route('activation.show', ['token' => $token->token]));

        $response->assertStatus(200);
        $response->assertSee('Token ini sudah digunakan');
    }

    public function test_can_activate_account_with_valid_token()
    {
        $user = User::create(['email' => 'test@example.com', 'password' => Hash::make('temp'), 'status' => 'pending']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '12345', 'full_name' => 'Test', 'division_id' => $this->division->id]);
        $token = ActivationToken::generate($member);

        $response = $this->post(route('activation.activate', ['token' => $token->token]), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('activation.success'));

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        $token->refresh();
        $this->assertNotNull($token->used_at);
    }

    public function test_activation_requires_password_confirmation()
    {
        $user = User::create(['email' => 'test@example.com', 'password' => Hash::make('temp'), 'status' => 'pending']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '12345', 'full_name' => 'Test', 'division_id' => $this->division->id]);
        $token = ActivationToken::generate($member);

        $response = $this->post(route('activation.activate', ['token' => $token->token]), [
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertEquals('pending', $user->status);
    }

    public function test_activation_requires_minimum_password_length()
    {
        $user = User::create(['email' => 'test@example.com', 'password' => Hash::make('temp'), 'status' => 'pending']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '12345', 'full_name' => 'Test', 'division_id' => $this->division->id]);
        $token = ActivationToken::generate($member);

        $response = $this->post(route('activation.activate', ['token' => $token->token]), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_cannot_activate_with_expired_token()
    {
        $user = User::create(['email' => 'test@example.com', 'password' => Hash::make('temp'), 'status' => 'pending']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '12345', 'full_name' => 'Test', 'division_id' => $this->division->id]);
        $token = ActivationToken::create([
            'member_id' => $member->id,
            'token' => 'expired-token',
            'expires_at' => now()->subHours(1),
        ]);

        $response = $this->post(route('activation.activate', ['token' => $token->token]), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $user->refresh();
        $this->assertEquals('pending', $user->status);
    }

    public function test_activation_success_page_is_accessible()
    {
        $response = $this->get(route('activation.success'));
        
        $response->assertStatus(200);
        $response->assertSee('Akun Berhasil Diaktifkan');
    }
}