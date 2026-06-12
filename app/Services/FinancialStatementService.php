<?php

namespace App\Services;

use App\Abstracts\AbstractService;
use App\Repositories\FinancialStatementRepository;

class FinancialStatementService extends AbstractService
{
    public function __construct(FinancialStatementRepository $repository)
    {
        $this->repository = $repository;
    }

    public function beforeSave(array $data): array
    {
        return $this->resolveHashParams($data, [
            'requester_hash' => UserService::class,
            'receiver_hash' => UserService::class,
        ]);
    }
}
