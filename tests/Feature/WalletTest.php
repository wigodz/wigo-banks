<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Events\WithdrawalCodeRequested;
use App\Models\FinancialStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_balance(): void
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

        $response = $this->actingAs($user)->getJson('/wallet/balance');

        $response->assertOk();
        $response->assertJsonPath('data.balance', 1000);
    }

    public function test_authenticated_user_can_get_balance_history(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/wallet/balance-history');

        $response->assertOk();
        $response->assertJsonCount(7, 'data.history');
        $response->assertJsonPath('data.history.6.balance', 0);
    }

    public function test_authenticated_user_can_get_summary(): void
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

        $response = $this->actingAs($user)->getJson('/wallet/summary');

        $response->assertOk();
        $response->assertJsonPath('data.balance', 700);
        $response->assertJsonPath('data.received', 1000);
        $response->assertJsonPath('data.sent', 300);
    }

    public function test_authenticated_user_can_get_transactions(): void
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

        $response = $this->actingAs($user)->getJson('/wallet/transactions');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.transactions');
        $response->assertJsonStructure([
            'data' => [
                'transactions' => [
                    '*' => ['hash', 'amount', 'type', 'operation_type', 'receiver', 'created_at'],
                ],
            ],
        ]);
    }

    public function test_authenticated_user_can_make_a_deposit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/wallet/deposits', ['amount' => 1000]);

        $response->assertOk();
        $this->assertDatabaseHas('financial_statements', [
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);
    }

    public function test_deposit_fails_when_amount_is_not_positive(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/wallet/deposits', ['amount' => 0]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('amount');
    }

    public function test_deposit_fails_when_amount_exceeds_the_maximum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/wallet/deposits', ['amount' => 10000000]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('amount');
    }

    public function test_authenticated_user_can_request_a_withdrawal(): void
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

        $response = $this->actingAs($user)->postJson('/wallet/withdrawals', ['amount' => 500]);

        $response->assertOk();
        Event::assertDispatched(WithdrawalCodeRequested::class);
    }

    public function test_withdrawal_request_fails_when_amount_exceeds_balance(): void
    {
        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson('/wallet/withdrawals', ['amount' => 1500]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('amount');
    }

    public function test_authenticated_user_can_confirm_a_withdrawal_with_the_correct_code(): void
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

        $this->actingAs($user)->postJson('/wallet/withdrawals', ['amount' => 500]);

        $code = '';
        Event::assertDispatched(WithdrawalCodeRequested::class, function ($event) use (&$code) {
            $code = $event->code;

            return true;
        });

        $response = $this->actingAs($user)->postJson('/wallet/withdrawals/confirm', ['code' => $code]);

        $response->assertOk();
        $this->assertDatabaseHas('financial_statements', [
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Withdrawal,
            'type' => MovementType::Negative,
            'amount' => 500,
        ]);
    }

    public function test_confirming_a_withdrawal_with_an_incorrect_code_fails(): void
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

        $this->actingAs($user)->postJson('/wallet/withdrawals', ['amount' => 500]);

        $response = $this->actingAs($user)->postJson('/wallet/withdrawals/confirm', ['code' => 'WRONGCODE123']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('code');
        $this->assertDatabaseMissing('financial_statements', [
            'operation_type' => OperationType::Withdrawal,
        ]);
    }

    public function test_guest_cannot_access_wallet_endpoints(): void
    {
        $this->getJson('/wallet/balance')->assertUnauthorized();
        $this->getJson('/wallet/balance-history')->assertUnauthorized();
        $this->getJson('/wallet/summary')->assertUnauthorized();
        $this->getJson('/wallet/transactions')->assertUnauthorized();
        $this->postJson('/wallet/deposits', ['amount' => 100])->assertUnauthorized();
        $this->postJson('/wallet/withdrawals', ['amount' => 100])->assertUnauthorized();
        $this->postJson('/wallet/withdrawals/confirm', ['code' => 'X'])->assertUnauthorized();
    }
}
