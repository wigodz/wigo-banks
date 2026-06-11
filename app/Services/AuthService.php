<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = $this->userService->save([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return [
            'user' => $user,
            'token' => $user->createToken('spa')->accessToken,
        ];
    }

    /**
     * @return array{user: User, token: string}
     */
    public function login(array $credentials, bool $remember = false): array
    {
        $user = $this->userService->findOneWhere(['email' => $credentials['email']]);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        Auth::login($user, $remember);

        return [
            'user' => $user,
            'token' => $user->createToken('spa')->accessToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->token()?->revoke();

        Auth::guard('web')->logout();
    }
}
