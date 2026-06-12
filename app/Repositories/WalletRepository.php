<?php

namespace App\Repositories;

use App\Abstracts\AbstractRepository;
use App\Enums\MovementType;
use App\Models\FinancialStatement;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

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
}
