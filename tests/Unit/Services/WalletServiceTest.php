<?php

namespace Tests\Unit\Services;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Events\WithdrawalCodeRequested;
use App\Models\FinancialStatement;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
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

    public function test_get_summary_returns_balance_received_and_sent_in_the_last_month(): void
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

        FinancialStatement::factory()->create([
            'requester_id' => $other->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 500,
            'created_at' => now()->subMonths(2),
        ]);

        $result = app(WalletService::class)->getSummary($user);

        $this->assertSame([
            'balance' => 1200,
            'received' => 1000,
            'sent' => 300,
        ], $result);
    }

    public function test_request_withdrawal_dispatches_event_with_code_when_amount_is_valid(): void
    {
        Event::fake();

        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        app(WalletService::class)->requestWithdrawal($user, 500);

        Event::assertDispatched(WithdrawalCodeRequested::class, fn ($event) => $event->user->is($user) && \strlen($event->code) === 12);
    }

    public function test_request_withdrawal_fails_when_amount_exceeds_balance(): void
    {
        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $this->expectException(ValidationException::class);

        app(WalletService::class)->requestWithdrawal($user, 1500);
    }

    public function test_request_withdrawal_fails_when_amount_is_not_positive(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(WalletService::class)->requestWithdrawal($user, 0);
    }

    public function test_confirm_withdrawal_creates_financial_statement_when_code_is_correct(): void
    {
        Event::fake();

        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $service = app(WalletService::class);
        $service->requestWithdrawal($user, 500);

        $code = '';
        Event::assertDispatched(WithdrawalCodeRequested::class, function ($event) use (&$code) {
            $code = $event->code;

            return true;
        });

        $statement = $service->confirmWithdrawal($user, $code);

        $this->assertSame(OperationType::Withdrawal, $statement->operation_type);
        $this->assertSame(MovementType::Negative, $statement->type);
        $this->assertSame(500, $statement->amount);
        $this->assertSame($user->id, $statement->requester_id);
        $this->assertSame($user->id, $statement->receiver_id);
        $this->assertNull(Cache::get("withdrawal-code:{$user->id}"));
    }

    public function test_confirm_withdrawal_fails_when_code_is_incorrect(): void
    {
        Event::fake();

        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $service = app(WalletService::class);
        $service->requestWithdrawal($user, 500);

        $this->expectException(ValidationException::class);

        $service->confirmWithdrawal($user, 'WRONGCODE123');
    }
}
