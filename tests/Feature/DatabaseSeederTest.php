<?php

namespace Tests\Feature;

use App\Enums\OperationType;
use App\Models\FinancialStatement;
use App\Models\User;
use App\Services\WalletService;
use Database\Seeders\UserSeeder;
use Database\Seeders\WalletSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_populate_users_with_hashes_and_known_credentials(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertSame(4, User::count());

        $test = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($test);
        $this->assertNotNull($test->hash);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $test->password));
    }

    public function test_wallet_seeder_creates_consistent_movements_and_balances(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(WalletSeeder::class);

        $test = User::where('email', 'test@example.com')->first();

        $this->assertSame(255000, app(WalletService::class)->getBalance($test)['balance']);

        $transfer = FinancialStatement::where('operation_type', OperationType::Transfer)->first();
        $this->assertNotNull($transfer->reference_id);

        $this->assertTrue(FinancialStatement::where('reversed', true)->exists());
        $this->assertTrue(FinancialStatement::where('operation_type', OperationType::Reversal)->exists());
    }
}