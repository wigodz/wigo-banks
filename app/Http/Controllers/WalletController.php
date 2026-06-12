<?php

namespace App\Http\Controllers;

use App\Abstracts\AbstractController;
use App\Http\Requests\ConfirmWithdrawalRequest;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawalRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

    public function summary(Request $request): JsonResponse
    {
        return $this->ok($this->walletService->getSummary($request->user()));
    }

    public function transactions(Request $request): JsonResponse
    {
        return $this->ok($this->walletService->getTransactions($request->user()));
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $this->walletService->deposit($request->user(), $request->validated('amount'));

        return $this->success('Depósito realizado com sucesso');
    }

    public function requestWithdrawal(WithdrawalRequest $request): JsonResponse
    {
        try {
            $this->walletService->requestWithdrawal($request->user(), $request->validated('amount'));

            return $this->success('Código de confirmação enviado para o seu e-mail');
        } catch (ValidationException $e) {
            return $this->error($this->messageErrorDefault, $e->errors());
        }
    }

    public function confirmWithdrawal(ConfirmWithdrawalRequest $request): JsonResponse
    {
        try {
            $this->walletService->confirmWithdrawal($request->user(), $request->validated('code'));

            return $this->success('Saque confirmado com sucesso');
        } catch (ValidationException $e) {
            return $this->error($this->messageErrorDefault, $e->errors());
        }
    }
}
