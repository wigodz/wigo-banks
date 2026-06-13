<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Events\WithdrawalCodeRequested;
use App\Models\FinancialStatement;
use App\Models\User;
use App\Services\WalletService;
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

    public function test_authenticated_user_can_get_paginated_transaction_history(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FinancialStatement::factory()->count(12)->create([
            'requester_id' => $other->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user)->getJson('/wallet/history');

        $response->assertOk();
        $response->assertJsonCount(10, 'data.transactions');
        $response->assertJsonPath('data.pagination.current_page', 1);
        $response->assertJsonPath('data.pagination.last_page', 2);
        $response->assertJsonPath('data.pagination.per_page', 10);
        $response->assertJsonPath('data.pagination.total', 12);
        $response->assertJsonStructure([
            'data' => [
                'transactions' => [
                    '*' => ['hash', 'amount', 'type', 'operation_type', 'reversed', 'receiver', 'created_at'],
                ],
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ],
        ]);
    }

    public function test_transaction_history_returns_the_requested_page(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FinancialStatement::factory()->count(12)->create([
            'requester_id' => $other->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user)->getJson('/wallet/history?page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data.transactions');
        $response->assertJsonPath('data.pagination.current_page', 2);
    }

    public function test_transaction_history_can_be_filtered_by_operation_type(): void
    {
        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
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

        $response = $this->actingAs($user)->getJson('/wallet/history?operation_type='.OperationType::Withdrawal->value);

        $response->assertOk();
        $response->assertJsonCount(1, 'data.transactions');
        $response->assertJsonPath('data.transactions.0.operation_type', 'Saque');
    }

    public function test_transaction_history_can_be_filtered_by_movement_type(): void
    {
        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
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

        $response = $this->actingAs($user)->getJson('/wallet/history?type='.MovementType::Negative->value);

        $response->assertOk();
        $response->assertJsonCount(1, 'data.transactions');
        $response->assertJsonPath('data.transactions.0.type', MovementType::Negative->value);
    }

    public function test_transaction_history_can_be_filtered_by_date_range(): void
    {
        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
            'created_at' => '2026-06-01 10:00:00',
        ]);

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 2000,
            'created_at' => '2026-06-10 10:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/wallet/history?date_from=2026-06-05&date_to=2026-06-15');

        $response->assertOk();
        $response->assertJsonCount(1, 'data.transactions');
        $response->assertJsonPath('data.transactions.0.amount', 2000);
    }

    public function test_transaction_history_can_be_filtered_by_receiver(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $other = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $sender->id,
            'receiver_id' => $sender->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        app(WalletService::class)->transfer($sender, $recipient, 400);
        app(WalletService::class)->transfer($sender, $other, 100);

        $response = $this->actingAs($sender)->getJson('/wallet/history?receiver='.$recipient->hash);

        $response->assertOk();
        $response->assertJsonCount(1, 'data.transactions');
        $response->assertJsonPath('data.transactions.0.type', MovementType::Negative->value);
        $response->assertJsonPath('data.transactions.0.amount', 400);
        $response->assertJsonPath('data.transactions.0.receiver', $recipient->name);
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

    public function test_authenticated_user_can_get_transfer_recipients(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/wallet/recipients');

        $response->assertOk();
        $response->assertJsonPath('data.recipients.0.hash', $other->hash);
        $response->assertJsonPath('data.recipients.0.name', $other->name);
        $response->assertJsonCount(1, 'data.recipients');
    }

    public function test_authenticated_user_can_make_a_transfer(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $sender->id,
            'receiver_id' => $sender->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($sender)->postJson('/wallet/transfers', [
            'amount' => 400,
            'receiver' => $recipient->hash,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('financial_statements', [
            'requester_id' => $sender->id,
            'receiver_id' => $sender->id,
            'operation_type' => OperationType::Transfer,
            'type' => MovementType::Negative,
            'amount' => 400,
        ]);
        $this->assertDatabaseHas('financial_statements', [
            'requester_id' => $sender->id,
            'receiver_id' => $recipient->id,
            'operation_type' => OperationType::Transfer,
            'type' => MovementType::Positive,
            'amount' => 400,
        ]);
    }

    public function test_transfer_fails_when_amount_exceeds_balance(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $sender->id,
            'receiver_id' => $sender->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($sender)->postJson('/wallet/transfers', [
            'amount' => 1500,
            'receiver' => $recipient->hash,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('amount');
    }

    public function test_transfer_fails_when_receiver_is_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/wallet/transfers', [
            'amount' => 100,
            'receiver' => $user->hash,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('receiver');
    }

    public function test_transfer_fails_when_receiver_does_not_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/wallet/transfers', [
            'amount' => 100,
            'receiver' => 'invalidhash',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('receiver');
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

    public function test_authenticated_user_can_reverse_a_deposit(): void
    {
        $user = User::factory()->create();

        $deposit = FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson('/wallet/reversals', ['transaction' => $deposit->hash]);

        $response->assertOk();
        $this->assertTrue($deposit->fresh()->reversed);
        $this->assertDatabaseHas('financial_statements', [
            'reference_id' => $deposit->id,
            'operation_type' => OperationType::Reversal,
            'type' => MovementType::Negative,
            'amount' => 1000,
            'reversed' => true,
        ]);
    }

    public function test_authenticated_user_can_reverse_a_transfer(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $sender->id,
            'receiver_id' => $sender->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $debit = app(WalletService::class)->transfer($sender, $recipient, 400);

        $response = $this->actingAs($sender)->postJson('/wallet/reversals', ['transaction' => $debit->hash]);

        $response->assertOk();
        $this->assertSame(2, FinancialStatement::where('operation_type', OperationType::Reversal)->count());
        $this->assertSame(1000, app(WalletService::class)->getBalance($sender)['balance']);
        $this->assertSame(0, app(WalletService::class)->getBalance($recipient)['balance']);
    }

    public function test_reversing_an_already_reversed_transaction_fails(): void
    {
        $user = User::factory()->create();

        $deposit = FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $this->actingAs($user)->postJson('/wallet/reversals', ['transaction' => $deposit->hash])->assertOk();

        $response = $this->actingAs($user)->postJson('/wallet/reversals', ['transaction' => $deposit->hash]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.transaction.0', 'Esta transação já foi revertida.');
    }

    public function test_reversing_a_positive_statement_fails_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create();

        $deposit = FinancialStatement::factory()->create([
            'requester_id' => $user->id,
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

        $response = $this->actingAs($user)->postJson('/wallet/reversals', ['transaction' => $deposit->hash]);

        $response->assertUnprocessable();
        $response->assertJsonPath('errors.transaction.0', 'Saldo insuficiente para reverter esta movimentação.');
        $this->assertFalse($deposit->fresh()->reversed);
    }

    public function test_user_cannot_reverse_a_transaction_they_did_not_request(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $deposit = FinancialStatement::factory()->create([
            'requester_id' => $other->id,
            'receiver_id' => $other->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson('/wallet/reversals', ['transaction' => $deposit->hash]);

        $response->assertUnprocessable();
        $this->assertFalse($deposit->fresh()->reversed);
    }

    public function test_guest_cannot_access_wallet_endpoints(): void
    {
        $this->getJson('/wallet/balance')->assertUnauthorized();
        $this->getJson('/wallet/balance-history')->assertUnauthorized();
        $this->getJson('/wallet/summary')->assertUnauthorized();
        $this->getJson('/wallet/transactions')->assertUnauthorized();
        $this->getJson('/wallet/history')->assertUnauthorized();
        $this->getJson('/wallet/recipients')->assertUnauthorized();
        $this->postJson('/wallet/transfers', ['amount' => 100, 'receiver' => 'X'])->assertUnauthorized();
        $this->postJson('/wallet/deposits', ['amount' => 100])->assertUnauthorized();
        $this->postJson('/wallet/withdrawals', ['amount' => 100])->assertUnauthorized();
        $this->postJson('/wallet/withdrawals/confirm', ['code' => 'X'])->assertUnauthorized();
        $this->postJson('/wallet/reversals', ['transaction' => 'X'])->assertUnauthorized();
    }
}
