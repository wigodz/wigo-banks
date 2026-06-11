<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesPersonalAccessClient;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use CreatesPersonalAccessClient, RefreshDatabase;

    public function test_login_sends_two_factor_code_and_redirects_to_challenge(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    }

    public function test_two_factor_challenge_screen_redirects_to_login_without_pending_authentication(): void
    {
        $response = $this->get(route('two-factor.challenge'));

        $response->assertRedirect(route('login'));
    }

    public function test_two_factor_challenge_screen_can_be_rendered_after_login(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response = $this->get(route('two-factor.challenge'));

        $response->assertOk();
    }

    public function test_user_can_confirm_with_valid_two_factor_code(): void
    {
        $this->createPersonalAccessClient();

        $user = User::factory()->create();

        $code = 'ABC123';
        $this->withSession([
            'auth.two_factor.user_id' => $user->id,
            'auth.two_factor.remember' => false,
        ]);
        Cache::put("two-factor-code:{$user->id}", Hash::make($code), now()->addMinutes(5));

        $response = $this->post(route('two-factor.confirm'), ['code' => $code]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $response->assertSessionHas('auth.token');
    }

    public function test_user_cannot_confirm_with_invalid_two_factor_code(): void
    {
        $user = User::factory()->create();

        $this->withSession([
            'auth.two_factor.user_id' => $user->id,
            'auth.two_factor.remember' => false,
        ]);
        Cache::put("two-factor-code:{$user->id}", Hash::make('ABC123'), now()->addMinutes(5));

        $response = $this->post(route('two-factor.confirm'), ['code' => 'WRONG1']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_two_factor_code_expires_after_successful_confirmation(): void
    {
        $user = User::factory()->create();

        $code = 'ABC123';
        $this->withSession([
            'auth.two_factor.user_id' => $user->id,
            'auth.two_factor.remember' => false,
        ]);
        Cache::put("two-factor-code:{$user->id}", Hash::make($code), now()->addMinutes(5));

        $this->post(route('two-factor.confirm'), ['code' => $code]);

        $this->assertFalse(Cache::has("two-factor-code:{$user->id}"));
    }

    public function test_confirm_two_factor_code_redirects_to_login_without_pending_authentication(): void
    {
        $response = $this->post(route('two-factor.confirm'), ['code' => 'ABC123']);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
