<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Models\FinancialStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_guest_cannot_access_wallet_endpoints(): void
    {
        $this->getJson('/wallet/balance')->assertRedirect(route('login'));
        $this->getJson('/wallet/balance-history')->assertRedirect(route('login'));
    }
}
