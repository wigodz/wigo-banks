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

    public function test_get_balance_ignores_entries_where_user_is_only_the_requester(): void
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

        $this->assertSame(['balance' => 0], app(WalletService::class)->getBalance($user));
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

    public function test_deposit_creates_a_financial_statement_for_the_user(): void
    {
        $user = User::factory()->create();

        $statement = app(WalletService::class)->deposit($user, 1000);

        $this->assertSame(OperationType::Deposit, $statement->operation_type);
        $this->assertSame(MovementType::Positive, $statement->type);
        $this->assertSame(1000, $statement->amount);
        $this->assertSame($user->id, $statement->requester_id);
        $this->assertSame($user->id, $statement->receiver_id);
    }

    public function test_transfer_creates_financial_statements_for_sender_and_recipient(): void
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

        $statement = app(WalletService::class)->transfer($sender, $recipient, 400);

        $this->assertSame(OperationType::Transfer, $statement->operation_type);
        $this->assertSame(MovementType::Negative, $statement->type);
        $this->assertSame(400, $statement->amount);
        $this->assertSame($sender->id, $statement->requester_id);
        $this->assertSame($sender->id, $statement->receiver_id);

        $this->assertSame(600, app(WalletService::class)->getBalance($sender)['balance']);
        $this->assertSame(400, app(WalletService::class)->getBalance($recipient)['balance']);
    }

    public function test_transfer_links_the_correspondent_statements_through_a_reference(): void
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
        $debit->refresh();

        $credit = FinancialStatement::query()
            ->where('receiver_id', $recipient->id)
            ->where('type', MovementType::Positive)
            ->firstOrFail();

        $this->assertSame($credit->id, $debit->reference_id);
        $this->assertSame($debit->id, $credit->reference_id);
    }

    public function test_get_transaction_history_filtered_by_receiver_returns_the_outgoing_debit(): void
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

        app(WalletService::class)->transfer($sender, $recipient, 400);

        $result = app(WalletService::class)->getTransactionHistory($sender, ['receiver_id' => $recipient->id]);

        $this->assertCount(1, $result['transactions']);
        $this->assertSame(MovementType::Negative, $result['transactions'][0]['type']);
        $this->assertSame(400, $result['transactions'][0]['amount']);
        $this->assertSame($recipient->name, $result['transactions'][0]['receiver']);
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

        $this->expectException(ValidationException::class);

        app(WalletService::class)->transfer($sender, $recipient, 1500);
    }

    public function test_transfer_fails_when_recipient_is_the_sender(): void
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

        app(WalletService::class)->transfer($user, $user, 100);
    }

    public function test_get_transactions_returns_the_latest_transactions(): void
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

        $result = app(WalletService::class)->getTransactions($user);

        $this->assertCount(2, $result['transactions']);
        $this->assertSame(['hash', 'amount', 'type', 'operation_type', 'receiver', 'created_at'], array_keys($result['transactions'][0]));
        $this->assertSame(300, $result['transactions'][0]['amount']);
        $this->assertSame('Saque', $result['transactions'][0]['operation_type']);
        $this->assertSame($user->name, $result['transactions'][0]['receiver']);
    }

    public function test_get_transactions_shows_a_transfer_to_both_sender_and_recipient(): void
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

        app(WalletService::class)->transfer($sender, $recipient, 400);

        $senderTransactions = app(WalletService::class)->getTransactions($sender)['transactions'];
        $recipientTransactions = app(WalletService::class)->getTransactions($recipient)['transactions'];

        $this->assertCount(2, $senderTransactions);
        $this->assertSame('Transferência', $senderTransactions[0]['operation_type']);
        $this->assertSame(400, $senderTransactions[0]['amount']);
        $this->assertSame('Depósito', $senderTransactions[1]['operation_type']);
        $this->assertSame(1000, $senderTransactions[1]['amount']);

        $this->assertCount(1, $recipientTransactions);
        $this->assertSame('Transferência', $recipientTransactions[0]['operation_type']);
        $this->assertSame(400, $recipientTransactions[0]['amount']);
    }

    public function test_get_transactions_limits_results_to_ten(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FinancialStatement::factory()->count(12)->create([
            'requester_id' => $other->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 100,
        ]);

        $result = app(WalletService::class)->getTransactions($user);

        $this->assertCount(10, $result['transactions']);
    }

    public function test_get_transaction_history_returns_paginated_transactions_with_the_reversed_flag(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FinancialStatement::factory()->count(12)->create([
            'requester_id' => $other->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 100,
        ]);

        $result = app(WalletService::class)->getTransactionHistory($user);

        $this->assertCount(10, $result['transactions']);
        $this->assertSame(
            ['hash', 'amount', 'type', 'operation_type', 'receiver', 'created_at', 'reversed', 'reversible'],
            array_keys($result['transactions'][0]),
        );
        $this->assertSame([
            'current_page' => 1,
            'last_page' => 2,
            'per_page' => 10,
            'total' => 12,
        ], $result['pagination']);
    }

    public function test_reverse_deposit_creates_an_opposite_reversal_and_marks_both_as_reversed(): void
    {
        $user = User::factory()->create();

        $deposit = FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $reversal = app(WalletService::class)->reverse($user, $deposit->hash);

        $this->assertSame(OperationType::Reversal, $reversal->operation_type);
        $this->assertSame(MovementType::Negative, $reversal->type);
        $this->assertSame(1000, $reversal->amount);
        $this->assertSame($deposit->id, $reversal->reference_id);
        $this->assertTrue($reversal->reversed);
        $this->assertTrue($deposit->fresh()->reversed);
        $this->assertSame(0, app(WalletService::class)->getBalance($user)['balance']);
    }

    public function test_reverse_withdrawal_returns_the_amount_to_the_user(): void
    {
        $user = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $withdrawal = FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Withdrawal,
            'type' => MovementType::Negative,
            'amount' => 300,
        ]);

        $reversal = app(WalletService::class)->reverse($user, $withdrawal->hash);

        $this->assertSame(OperationType::Reversal, $reversal->operation_type);
        $this->assertSame(MovementType::Positive, $reversal->type);
        $this->assertTrue($withdrawal->fresh()->reversed);
        $this->assertSame(1000, app(WalletService::class)->getBalance($user)['balance']);
    }

    public function test_reverse_transfer_reverses_both_statements_for_sender_and_recipient(): void
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

        app(WalletService::class)->reverse($sender, $debit->hash);

        $debit->refresh();
        $credit = $debit->reference;

        $this->assertTrue($debit->reversed);
        $this->assertTrue($credit->reversed);

        // Saldos restaurados: remetente volta a ter 1000 e o recebedor zera.
        $this->assertSame(1000, app(WalletService::class)->getBalance($sender)['balance']);
        $this->assertSame(0, app(WalletService::class)->getBalance($recipient)['balance']);

        $this->assertSame(2, FinancialStatement::where('operation_type', OperationType::Reversal)->count());
    }

    public function test_reverse_fails_when_transaction_is_already_reversed(): void
    {
        $user = User::factory()->create();

        $deposit = FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        app(WalletService::class)->reverse($user, $deposit->hash);

        $this->expectException(ValidationException::class);

        app(WalletService::class)->reverse($user, $deposit->fresh()->hash);
    }

    public function test_reverse_fails_when_user_is_not_the_requester(): void
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

        $this->expectException(ValidationException::class);

        app(WalletService::class)->reverse($user, $deposit->hash);
    }

    public function test_history_reversible_flag_reflects_available_balance(): void
    {
        $user = User::factory()->create();

        $deposit = FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Deposit,
            'type' => MovementType::Positive,
            'amount' => 1000,
        ]);

        $withdrawal = FinancialStatement::factory()->create([
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'operation_type' => OperationType::Withdrawal,
            'type' => MovementType::Negative,
            'amount' => 300,
        ]);

        $transactions = collect(app(WalletService::class)->getTransactionHistory($user)['transactions']);

        // Saldo é 700: o depósito de 1000 não pode mais ser revertido, mas o saque sim.
        $this->assertFalse($transactions->firstWhere('hash', $deposit->hash)['reversible']);
        $this->assertTrue($transactions->firstWhere('hash', $withdrawal->hash)['reversible']);
    }

    public function test_reverse_fails_when_balance_is_insufficient_to_revert_a_positive_statement(): void
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

        $this->expectException(ValidationException::class);

        app(WalletService::class)->reverse($user, $deposit->hash);
    }

    public function test_reverse_transfer_fails_when_recipient_lacks_balance_to_return_the_amount(): void
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

        // O recebedor gasta parte do valor, ficando sem saldo para devolvê-lo.
        FinancialStatement::factory()->create([
            'requester_id' => $recipient->id,
            'receiver_id' => $recipient->id,
            'operation_type' => OperationType::Withdrawal,
            'type' => MovementType::Negative,
            'amount' => 200,
        ]);

        $this->expectException(ValidationException::class);

        app(WalletService::class)->reverse($sender, $debit->hash);
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
