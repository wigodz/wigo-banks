<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): RedirectResponse
    {
        $result = $this->authService->register($request->validated());

        $request->session()->flash('auth.token', $result['token']);

        return to_route('dashboard');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $result = $this->authService->login(
            $request->only('email', 'password'),
            $request->boolean('remember'),
        );

        $request->session()->regenerate();
        $request->session()->flash('auth.token', $result['token']);

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
