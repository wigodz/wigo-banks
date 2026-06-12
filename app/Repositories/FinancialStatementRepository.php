<?php

namespace App\Repositories;

use App\Abstracts\AbstractRepository;
use App\Models\FinancialStatement;

class FinancialStatementRepository extends AbstractRepository
{
    public function __construct(FinancialStatement $model)
    {
        $this->model = $model;
    }
}
