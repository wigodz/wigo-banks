<?php

namespace Tests\Unit\Listeners;

use App\Events\UserCreated;
use App\Listeners\UserCreated\SendWelcomeNotification;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendWelcomeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_the_welcome_notification_to_the_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        (new SendWelcomeNotification)->handle(new UserCreated($user));

        Notification::assertSentTo($user, WelcomeNotification::class);
    }
}
