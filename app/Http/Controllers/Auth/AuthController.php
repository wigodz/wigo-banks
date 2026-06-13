<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): RedirectResponse
    {
        $this->authService->register($request->validated());

        $request->session()->regenerate();

        return to_route('dashboard');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $this->authService->login(
            $request->only('email', 'password'),
            $request->boolean('remember'),
        );

        return to_route('two-factor.challenge');
    }

    public function twoFactorChallenge(): Response|RedirectResponse
    {
        if (! $this->authService->hasPendingTwoFactorChallenge()) {
            return to_route('login');
        }

        return Inertia::render('auth/TwoFactorChallenge');
    }

    public function confirmTwoFactor(TwoFactorChallengeRequest $request): RedirectResponse
    {
        if (! $this->authService->hasPendingTwoFactorChallenge()) {
            return to_route('login');
        }

        $this->authService->confirmTwoFactorCode($request->validated('code'));

        $request->session()->regenerate();

        return to_route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout($request->user());

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
