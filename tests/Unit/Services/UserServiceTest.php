<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_transfer_recipients_returns_every_user_except_the_authenticated_one(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $result = app(UserService::class)->getTransferRecipients($user);

        $this->assertSame([
            'recipients' => [
                ['hash' => $other->hash, 'name' => $other->name],
            ],
        ], $result);
    }
}
