<?php

namespace Database\Factories;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Models\FinancialStatement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialStatementFactory extends Factory
{
    protected $model = FinancialStatement::class;

    public function definition(): array
    {
        return [
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'reversed' => false,
            'requester_id' => User::factory(),
            'receiver_id' => User::factory(),
            'amount' => fake()->numberBetween(100, 100000),
        ];
    }
}
