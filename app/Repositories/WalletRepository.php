<?php

namespace App\Repositories;

use App\Abstracts\AbstractRepository;
use App\Enums\MovementType;
use App\Models\FinancialStatement;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
        return $this->scopeUserTransactions($this->model->query(), $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function paginateTransactions(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->scopeUserTransactions($this->model->query(), $userId)
            ->with(['requester', 'receiver', 'reference.receiver']);

        $this->applyTransactionFilters($query, $filters);

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function scopeUserTransactions(Builder $query, int $userId): Builder
    {
        return $query
            ->with(['requester', 'receiver'])
            ->where(function (Builder $query) use ($userId) {
                $query->where(function (Builder $inner) use ($userId) {
                    $inner->where('requester_id', $userId)
                        ->where('receiver_id', $userId);
                })->orWhere(function (Builder $inner) use ($userId) {
                    $inner->where('receiver_id', $userId)
                        ->where('type', MovementType::Positive);
                });
            });
    }

    private function applyTransactionFilters(Builder $query, array $filters): void
    {
        if (isset($filters['operation_type'])) {
            $query->where('operation_type', $filters['operation_type']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['receiver_id'])) {
            $query->where('type', MovementType::Negative)
                ->whereHas('reference', fn (Builder $reference) => $reference->where('receiver_id', $filters['receiver_id']));
        }
    }

    public function findRequesterStatement(int $userId, string $hash): ?FinancialStatement
    {
        return $this->model->query()
            ->where('hash', $hash)
            ->where('requester_id', $userId)
            ->with('reference')
            ->first();
    }

    /**
     * Persiste os extratos de reversão e marca tanto os originais quanto as
     * reversões como revertidos, dentro de uma única transação.
     *
     * @param  Collection<int, FinancialStatement>  $originals
     * @param  array<int, array<string, mixed>>  $reversals
     */
    public function createReversals(Collection $originals, array $reversals): FinancialStatement
    {
        return DB::transaction(function () use ($originals, $reversals) {
            $created = collect($reversals)->map(fn (array $reversal) => $this->model->query()->create($reversal));

            $originals->each(fn (FinancialStatement $statement) => $statement->update(['reversed' => true]));
            $created->each(fn (FinancialStatement $statement) => $statement->update(['reversed' => true]));

            return $created->first();
        });
    }
}
