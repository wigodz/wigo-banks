<?php

namespace Tests\Unit\Services;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Models\User;
use App\Services\FinancialStatementService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialStatementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_before_save_resolves_requester_and_receiver_hashes_to_ids(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        $data = app(FinancialStatementService::class)->beforeSave([
            'operation_type' => OperationType::Deposit->value,
            'type' => MovementType::Positive->value,
            'requester_hash' => $requester->hash,
            'receiver_hash' => $receiver->hash,
            'amount' => 1000,
        ]);

        $this->assertSame($requester->id, $data['requester_id']);
        $this->assertSame($receiver->id, $data['receiver_id']);
    }

    public function test_before_save_throws_for_invalid_hash(): void
    {
        $receiver = User::factory()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found.');

        app(FinancialStatementService::class)->beforeSave([
            'operation_type' => OperationType::Deposit->value,
            'type' => MovementType::Positive->value,
            'requester_hash' => 'invalid-hash',
            'receiver_hash' => $receiver->hash,
            'amount' => 1000,
        ]);
    }
}
