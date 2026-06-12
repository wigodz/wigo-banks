<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Models\FinancialStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_financial_statement(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        $response = $this->actingAs($requester)->postJson('/financial-statements', [
            'operation_type' => OperationType::Deposit->value,
            'type' => MovementType::Positive->value,
            'requester_hash' => $requester->hash,
            'receiver_hash' => $receiver->hash,
            'amount' => 1000,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('financial_statements', [
            'operation_type' => OperationType::Deposit->value,
            'type' => MovementType::Positive->value,
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'amount' => 1000,
        ]);
    }

    public function test_creating_financial_statement_fails_with_invalid_requester_hash(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        $response = $this->actingAs($requester)->postJson('/financial-statements', [
            'operation_type' => OperationType::Deposit->value,
            'type' => MovementType::Positive->value,
            'requester_hash' => 'invalid-hash',
            'receiver_hash' => $receiver->hash,
            'amount' => 1000,
        ]);

        $response->assertUnprocessable();
    }

    public function test_authenticated_user_can_list_financial_statements(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        FinancialStatement::factory()->create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($requester)->getJson('/financial-statements');

        $response->assertOk();
        $response->assertJsonPath('data.data.0.requester.id', $requester->id);
        $response->assertJsonPath('data.data.0.receiver.id', $receiver->id);
    }

    public function test_authenticated_user_can_show_financial_statement(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        $statement = FinancialStatement::factory()->create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
        ]);

        $response = $this->actingAs($requester)->getJson("/financial-statements/{$statement->hash}");

        $response->assertOk();
        $response->assertJsonPath('data.hash', $statement->hash);
    }

    public function test_guest_cannot_access_financial_statements(): void
    {
        $response = $this->getJson('/financial-statements');

        $response->assertRedirect(route('login'));
    }
}
