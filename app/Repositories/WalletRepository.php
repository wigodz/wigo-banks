<?php

namespace App\Repositories;

use App\Abstracts\AbstractRepository;
use App\Enums\MovementType;
use App\Models\FinancialStatement;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WalletRepository extends AbstractRepository
{
    public function __construct(FinancialStatement $model)
    {
        $this->model = $model;
    }

    public function getBalance(int $userId): int
    {
        return (int) $this->model->query()
            ->where('receiver_id', $userId)
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE -amount END), 0) as balance', [
                MovementType::Positive->value,
            ])
            ->value('balance');
    }

    public function getMovementsByDay(int $userId, CarbonInterface $since): Collection
    {
        return $this->model->query()
            ->where('receiver_id', $userId)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, SUM(CASE WHEN type = ? THEN amount ELSE -amount END) as total', [
                MovementType::Positive->value,
            ])
            ->groupBy('date')
            ->pluck('total', 'date');
    }

    public function getSumByTypeSince(int $userId, MovementType $type, CarbonInterface $since): int
    {
        return (int) $this->model->query()
            ->where('receiver_id', $userId)
            ->where('type', $type)
            ->where('created_at', '>=', $since)
            ->sum('amount');
    }

    public function getLatestTransactions(int $userId, int $limit = 10): Collection
    {
        return $this->model->query()
            ->where('requester_id', $userId)
            ->where('receiver_id', $userId)
            ->orWhere(function ($query) use ($userId) {
                $query->where('receiver_id', $userId)
                    ->where('type', MovementType::Positive);
            })
            ->with(['requester', 'receiver'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function createTransfer(array $debit, array $credit): FinancialStatement
    {
        return DB::transaction(function () use ($debit, $credit) {
            $statement = $this->model->query()->create($debit);
            $this->model->query()->create($credit);

            return $statement;
        });
    }
}
