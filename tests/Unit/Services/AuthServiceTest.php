<?php

namespace Tests\Unit\Services;

use App\Events\TwoFactorCodeRequested;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_throws_validation_exception_for_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(AuthService::class)->login([
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    public function test_login_dispatches_two_factor_code_event_and_stores_pending_session(): void
    {
        Event::fake();

        $user = User::factory()->create();

        app(AuthService::class)->login([
            'email' => $user->email,
            'password' => 'password',
        ], remember: true);

        Event::assertDispatched(TwoFactorCodeRequested::class, fn ($event) => $event->user->is($user));

        $this->assertSame($user->id, session('auth.two_factor.user_id'));
        $this->assertTrue(session('auth.two_factor.remember'));
        $this->assertTrue(Cache::has("two-factor-code:{$user->id}"));
    }

    public function test_confirm_two_factor_code_logs_in_user_and_clears_cache(): void
    {
        $user = User::factory()->create();
        $code = 'ABC123';

        session([
            'auth.two_factor.user_id' => $user->id,
            'auth.two_factor.remember' => false,
        ]);
        Cache::put("two-factor-code:{$user->id}", Hash::make($code), now()->addMinutes(5));

        $result = app(AuthService::class)->confirmTwoFactorCode($code);

        $this->assertSame($user->id, $result->id);
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(Cache::has("two-factor-code:{$user->id}"));
        $this->assertFalse(session()->has('auth.two_factor.user_id'));
    }

    public function test_confirm_two_factor_code_throws_validation_exception_for_invalid_code(): void
    {
        $user = User::factory()->create();

        session(['auth.two_factor.user_id' => $user->id]);
        Cache::put("two-factor-code:{$user->id}", Hash::make('ABC123'), now()->addMinutes(5));

        $this->expectException(ValidationException::class);

        app(AuthService::class)->confirmTwoFactorCode('WRONG1');
    }

    public function test_confirm_two_factor_code_throws_validation_exception_when_code_expired(): void
    {
        $user = User::factory()->create();

        session(['auth.two_factor.user_id' => $user->id]);

        $this->expectException(ValidationException::class);

        app(AuthService::class)->confirmTwoFactorCode('ABC123');
    }
}
