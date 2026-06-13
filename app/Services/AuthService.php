<?php

namespace App\Services;

use App\Events\TwoFactorCodeRequested;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const TWO_FACTOR_TTL_MINUTES = 5;

    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function register(array $data): User
    {
        $user = $this->userService->save([
            'name' => data_get($data, 'name'),
            'email' => data_get($data, 'email'),
            'password' => Hash::make(data_get($data, 'password')),
        ]);

        Auth::login($user);

        return $user;
    }

    public function login(array $credentials, bool $remember = false): void
    {
        $user = $this->userService->findOneWhere(['email' => $credentials['email']]);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->sendTwoFactorCode($user);

        session([
            'auth.two_factor.user_id' => $user->id,
            'auth.two_factor.remember' => $remember,
        ]);
    }

    public function sendTwoFactorCode(User $user): void
    {
        $code = Str::upper(Str::random(6));

        Cache::put(
            $this->twoFactorCacheKey($user),
            Hash::make($code),
            now()->addMinutes(self::TWO_FACTOR_TTL_MINUTES),
        );

        event(new TwoFactorCodeRequested($user, $code));
    }

    public function hasPendingTwoFactorChallenge(): bool
    {
        return session()->has('auth.two_factor.user_id');
    }

    public function confirmTwoFactorCode(string $code): User
    {
        $user = User::findOrFail(session('auth.two_factor.user_id'));
        $remember = session('auth.two_factor.remember', false);

        $hashed = Cache::get($this->twoFactorCacheKey($user));

        if (! $hashed || ! Hash::check($code, $hashed)) {
            throw ValidationException::withMessages([
                'code' => __('auth.two_factor_invalid'),
            ]);
        }

        Cache::forget($this->twoFactorCacheKey($user));

        Auth::login($user, $remember);

        session()->forget(['auth.two_factor.user_id', 'auth.two_factor.remember']);

        return $user;
    }

    public function logout(User $user): void
    {
        // Revoga eventuais tokens de API do usuário, não apenas o da requisição.
        $user->tokens()->each(fn ($token) => $token->revoke());

        Auth::guard('web')->logout();
    }

    private function twoFactorCacheKey(User $user): string
    {
        return "two-factor-code:{$user->id}";
    }
}
