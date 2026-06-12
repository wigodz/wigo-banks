<?php

namespace App\Http\Controllers;

use App\Abstracts\AbstractController;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends AbstractController
{
    public function __construct(private readonly WalletService $walletService)
    {
        $this->service = $walletService;
    }

    public function balance(Request $request): JsonResponse
    {
        return $this->ok($this->walletService->getBalance($request->user()));
    }

    public function balanceHistory(Request $request): JsonResponse
    {
        return $this->ok($this->walletService->getBalanceHistory($request->user()));
    }
}
