<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Test User', 'email' => 'test@example.com'],
            ['name' => 'Maria Souza', 'email' => 'maria@example.com'],
            ['name' => 'João Pereira', 'email' => 'joao@example.com'],
            ['name' => 'Ana Lima', 'email' => 'ana@example.com'],
        ];

        foreach ($users as $user) {
            User::factory()->create($user);
        }
    }
}
