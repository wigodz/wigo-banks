<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        if (DB::table('oauth_clients')->where('grant_types', 'like', '%personal_access%')->doesntExist()) {
            Artisan::call('passport:client', [
                '--personal' => true,
                '--name' => config('app.name').' Personal Access Client',
                '--no-interaction' => true,
            ]);
        }
    }
}
