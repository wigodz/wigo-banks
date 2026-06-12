<?php

namespace Tests\Unit\Services;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Models\FinancialStatement;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_get_balance_returns_the_user_current_balance(): void
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

        $result = app(WalletService::class)->getBalance($user);

        $this->assertSame(['balance' => 1000], $result);
    }

    public function test_get_balance_history_returns_running_balance_for_the_last_seven_days(): void
    {
        $today = Carbon::create(2026, 6, 12, 12, 0, 0);
        $user = User::factory()->create();
        $other = User::factory()->create();

        Carbon::setTestNow($today->copy()->subDays(6));
        FinancialStatement::factory()->create([
            'requester_id' => $other->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        Carbon::setTestNow($today->copy()->subDays(2));
        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Withdrawal,
            'type' => MovementType::Negative,
            'amount' => 300,
        ]);

        Carbon::setTestNow($today);

        $result = app(WalletService::class)->getBalanceHistory($user);

        $this->assertSame(
            [1000, 1000, 1000, 1000, 700, 700, 700],
            array_column($result['history'], 'balance'),
        );

        $this->assertSame(
            [
                $today->copy()->subDays(6)->format('Y-m-d'),
                $today->copy()->subDays(5)->format('Y-m-d'),
                $today->copy()->subDays(4)->format('Y-m-d'),
                $today->copy()->subDays(3)->format('Y-m-d'),
                $today->copy()->subDays(2)->format('Y-m-d'),
                $today->copy()->subDays(1)->format('Y-m-d'),
                $today->format('Y-m-d'),
            ],
            array_column($result['history'], 'date'),
        );
    }
}
