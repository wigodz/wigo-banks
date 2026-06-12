<?php

namespace Tests\Unit\Common\Traits;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Models\FinancialStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_is_zero_without_financial_statements(): void
    {
        $user = User::factory()->create();

        $this->assertSame(0, $user->balance());
    }

    public function test_balance_sums_positive_and_subtracts_negative_entries(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $other->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Withdrawal,
            'type' => MovementType::Negative,
            'amount' => 300,
        ]);

        $this->assertSame(700, $user->balance());
    }

    public function test_balance_ignores_entries_where_user_is_only_the_requester(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $other->id,
            'operation_type' => OperationType::Transfer,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $this->assertSame(0, $user->balance());
    }
}
