<?php

namespace App\Services;

use App\Abstracts\AbstractService;
use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Events\WithdrawalCodeRequested;
use App\Models\FinancialStatement;
use App\Models\User;
use App\Repositories\WalletRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletService extends AbstractService
{
    private const WITHDRAWAL_CODE_LENGTH = 12;

    private const WITHDRAWAL_CODE_TTL_MINUTES = 5;

    private UserService $userService;

    public function __construct(WalletRepository $repository)
    {
        $this->repository = $repository;
        $this->userService = app(UserService::class);
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
            'transactions' => $transactions
                ->map(fn (FinancialStatement $statement) => $this->mapTransaction($statement))
                ->all(),
        ];
    }

    public function getTransactionHistory(User $user, array $filters = [], int $perPage = 10): array
    {
        $receiver = data_get($filters, 'receiver');

        if ($receiver) {
            data_set($filters, 'receiver_id', $this->userService->find($receiver)->id);
        }

        $filters = array_filter($filters, fn ($value) => $value !== null);

        $paginator = $this->repository->paginateTransactions($user->id, $filters, $perPage);

        return [
            'transactions' => $paginator->getCollection()
                ->map(fn (FinancialStatement $statement) => $this->mapTransaction($statement, detailed: true, userId: $user->id))
                ->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    private function mapTransaction(FinancialStatement $statement, bool $detailed = false, ?int $userId = null): array
    {
        $data = [
            'hash' => $statement->hash,
            'amount' => $statement->amount,
            'type' => $statement->type,
            'operation_type' => $statement->operation_type->label(),
            'receiver' => $this->resolveReceiverName($statement, $detailed),
            'created_at' => $statement->created_at,
        ];

        if ($detailed) {
            $data['reversed'] = $statement->reversed;
            $data['reversible'] = $this->isReversible($statement, $userId);
        }

        return $data;
    }

    private function isReversible(FinancialStatement $statement, ?int $userId): bool
    {
        if ($statement->reversed
            || $statement->requester_id !== $userId
            || $statement->operation_type === OperationType::Reversal) {
            return false;
        }

        return $this->hasBalanceForReversal($this->statementsToReverse($statement));
    }

    private function resolveReceiverName(FinancialStatement $statement, bool $detailed): string
    {
        if ($detailed && $statement->type === MovementType::Negative && $statement->reference) {
            return $statement->reference->receiver->name;
        }

        return $statement->receiver->name;
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

    public function transfer(User $sender, User $recipient, int $amount): FinancialStatement
    {
        if ($recipient->is($sender)) {
            throw ValidationException::withMessages([
                'receiver' => 'Não é possível transferir para a própria conta.',
            ]);
        }

        $balance = $this->repository->getBalance($sender->id);

        if ($amount <= 0 || $amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => 'O valor da transferência deve ser maior que zero e não pode exceder o saldo disponível.',
            ]);
        }

        return $this->repository->createTransfer(
            [
                'operation_type' => OperationType::Transfer,
                'type' => MovementType::Negative,
                'requester_id' => $sender->id,
                'receiver_id' => $sender->id,
                'amount' => $amount,
            ],
            [
                'operation_type' => OperationType::Transfer,
                'type' => MovementType::Positive,
                'requester_id' => $sender->id,
                'receiver_id' => $recipient->id,
                'amount' => $amount,
            ],
        );
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

    public function reverse(User $user, string $hash): FinancialStatement
    {
        $statement = $this->repository->findRequesterStatement($user->id, $hash);

        if (! $statement) {
            throw ValidationException::withMessages([
                'transaction' => 'Transação não encontrada ou não pode ser revertida por você.',
            ]);
        }

        if ($statement->reversed) {
            throw ValidationException::withMessages([
                'transaction' => 'Esta transação já foi revertida.',
            ]);
        }

        if ($statement->operation_type === OperationType::Reversal) {
            throw ValidationException::withMessages([
                'transaction' => 'Uma reversão não pode ser revertida.',
            ]);
        }

        $originals = $this->statementsToReverse($statement);

        if (! $this->hasBalanceForReversal($originals)) {
            throw ValidationException::withMessages([
                'transaction' => 'Saldo insuficiente para reverter esta movimentação.',
            ]);
        }

        $reversals = $originals->map(fn (FinancialStatement $original) => $this->buildReversal($original))->all();

        return $this->repository->createReversals($originals, $reversals);
    }

    private function statementsToReverse(FinancialStatement $statement): Collection
    {
        $originals = collect([$statement]);

        if ($statement->operation_type === OperationType::Transfer && $statement->reference) {
            $originals->push($statement->reference);
        }

        return $originals;
    }

    private function hasBalanceForReversal(Collection $originals): bool
    {
        return $originals
            ->filter(fn (FinancialStatement $statement) => $statement->type === MovementType::Positive)
            ->every(fn (FinancialStatement $statement) => $statement->amount <= $this->repository->getBalance($statement->receiver_id));
    }

    private function buildReversal(FinancialStatement $statement): array
    {
        return [
            'operation_type' => OperationType::Reversal,
            'type' => $statement->type === MovementType::Positive ? MovementType::Negative : MovementType::Positive,
            'requester_id' => $statement->requester_id,
            'receiver_id' => $statement->receiver_id,
            'reference_id' => $statement->id,
            'amount' => $statement->amount,
        ];
    }
}
