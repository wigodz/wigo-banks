<?php

namespace App\Services;

use App\Abstracts\AbstractService;
use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Events\WithdrawalCodeRequested;
use App\Models\FinancialStatement;
use App\Models\User;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletService extends AbstractService
{
    private const WITHDRAWAL_CODE_LENGTH = 12;

    private const WITHDRAWAL_CODE_TTL_MINUTES = 5;

    public function __construct(WalletRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getBalance(User $user): array
    {
        return ['balance' => $this->repository->getBalance($user->id)];
    }

    public function getBalanceHistory(User $user, int $days = 7): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $movementsByDay = $this->repository->getMovementsByDay($user->id, $since);

        $runningBalance = $this->repository->getBalance($user->id);
        $history = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $history[] = ['date' => $date, 'balance' => $runningBalance];
            $runningBalance -= (int) $movementsByDay->get($date, 0);
        }

        return ['history' => array_reverse($history)];
    }

    public function getSummary(User $user): array
    {
        $since = now()->subMonth();

        return [
            'balance' => $this->repository->getBalance($user->id),
            'received' => $this->repository->getSumByTypeSince($user->id, MovementType::Positive, $since),
            'sent' => $this->repository->getSumByTypeSince($user->id, MovementType::Negative, $since),
        ];
    }

    public function getTransactions(User $user): array
    {
        $transactions = $this->repository->getLatestTransactions($user->id);

        return [
            'transactions' => $transactions->map(fn (FinancialStatement $statement) => [
                'hash' => $statement->hash,
                'amount' => $statement->amount,
                'type' => $statement->type,
                'operation_type' => $statement->operation_type->label(),
                'receiver' => $statement->receiver->name,
                'created_at' => $statement->created_at,
            ])->all(),
        ];
    }

    public function deposit(User $user, int $amount): FinancialStatement
    {
        return $this->save([
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'amount' => $amount,
        ]);
    }

    public function requestWithdrawal(User $user, int $amount): void
    {
        $balance = $this->repository->getBalance($user->id);

        if ($amount <= 0 || $amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => 'O valor do saque deve ser maior que zero e não pode exceder o saldo disponível.',
            ]);
        }

        $code = Str::upper(Str::random(self::WITHDRAWAL_CODE_LENGTH));

        Cache::put($this->withdrawalCacheKey($user), [
            'code' => Hash::make($code),
            'amount' => $amount,
        ], now()->addMinutes(self::WITHDRAWAL_CODE_TTL_MINUTES));

        event(new WithdrawalCodeRequested($user, $code));
    }

    public function confirmWithdrawal(User $user, string $code): FinancialStatement
    {
        $pending = Cache::get($this->withdrawalCacheKey($user));

        if (! $pending || ! Hash::check($code, $pending['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Código de confirmação inválido.',
            ]);
        }

        Cache::forget($this->withdrawalCacheKey($user));

        return $this->save([
            'operation_type' => OperationType::Withdrawal,
            'type' => MovementType::Negative,
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'amount' => data_get($pending, 'amount'),
        ]);
    }

    private function withdrawalCacheKey(User $user): string
    {
        return "withdrawal-code:{$user->id}";
    }
}
