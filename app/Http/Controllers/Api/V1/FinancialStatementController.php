<?php

namespace App\Http\Controllers\Api\V1;

use App\Abstracts\AbstractController;
use App\Http\Requests\FinancialStatementRequest;
use App\Services\FinancialStatementService;

class FinancialStatementController extends AbstractController
{
    protected array $with = ['requester', 'receiver'];

    protected $requestValidate = FinancialStatementRequest::class;

    public function __construct(FinancialStatementService $financialStatementService)
    {
        $this->service = $financialStatementService;
    }
}
