<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Enums\OperationType;
use App\Models\FinancialStatement;
use App\Models\User;
use App\Services\WalletService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $wallet = app(WalletService::class);

        $test = User::where('email', 'test@example.com')->first();
        $maria = User::where('email', 'maria@example.com')->first();
        $joao = User::where('email', 'joao@example.com')->first();
        $ana = User::where('email', 'ana@example.com')->first();

        if (! $test || ! $maria || ! $joao || ! $ana) {
            return;
        }

        $this->deposit($wallet, $test, 250000, now()->subDays(6));
        $this->deposit($wallet, $test, 90000, now()->subDays(3));
        $this->deposit($wallet, $maria, 150000, now()->subDays(5));
        $this->deposit($wallet, $joao, 80000, now()->subDays(5));
        $this->deposit($wallet, $ana, 60000, now()->subDays(4));

        $wallet->transfer($test, $maria, 45000);
        $wallet->transfer($test, $joao, 30000);
        $wallet->transfer($maria, $ana, 20000);
        $wallet->transfer($joao, $test, 15000);

        $this->withdrawal($test, 25000, now()->subDays(1));

        $reverted = $this->deposit($wallet, $ana, 10000, now()->subDays(2));
        $wallet->reverse($ana, $reverted->hash);
    }

    private function deposit(WalletService $wallet, User $user, int $amount, CarbonInterface $date): FinancialStatement
    {
        $statement = $wallet->deposit($user, $amount);
        $statement->forceFill(['created_at' => $date, 'updated_at' => $date])->saveQuietly();

        return $statement;
    }

    private function withdrawal(User $user, int $amount, CarbonInterface $date): void
    {
        FinancialStatement::factory()->create([
            'operation_type' => OperationType::Withdrawal,
            'type' => MovementType::Negative,
            'requester_id' => $user->id,
            'receiver_id' => $user->id,
            'amount' => $amount,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
