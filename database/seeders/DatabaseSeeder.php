<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->doesntExist()) {
            $this->call([
                UserSeeder::class,
                WalletSeeder::class,
            ]);
        }

        if (DB::table('oauth_clients')->where('grant_types', 'like', '%personal_access%')->doesntExist()) {
            Artisan::call('passport:client', [
                '--personal' => true,
                '--name' => config('app.name').' Personal Access Client',
                '--no-interaction' => true,
            ]);
        }
    }
}
