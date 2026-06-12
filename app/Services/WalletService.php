<?php

namespace App\Services;

use App\Abstracts\AbstractService;
use App\Models\User;
use App\Repositories\WalletRepository;

class WalletService extends AbstractService
{
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
}
