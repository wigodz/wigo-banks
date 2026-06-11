<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;

trait CreatesPersonalAccessClient
{
    protected function createPersonalAccessClient(): void
    {
        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => config('app.name').' Personal Access Client',
            '--no-interaction' => true,
        ]);
    }
}
