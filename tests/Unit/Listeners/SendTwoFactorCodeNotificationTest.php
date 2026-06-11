<?php

namespace Tests\Unit\Listeners;

use App\Events\TwoFactorCodeRequested;
use App\Listeners\SendTwoFactorCodeNotification;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTwoFactorCodeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_is_dispatched_to_the_dedicated_login_queue(): void
    {
        $listener = new SendTwoFactorCodeNotification;

        $this->assertSame('login', $listener->queue);
    }

    public function test_notification_is_dispatched_to_the_dedicated_login_queue(): void
    {
        $notification = new TwoFactorCodeNotification('ABC123');

        $this->assertSame('login', $notification->queue);
    }

    public function test_handle_sends_the_notification_to_the_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        (new SendTwoFactorCodeNotification)->handle(new TwoFactorCodeRequested($user, 'ABC123'));

        Notification::assertSentTo($user, TwoFactorCodeNotification::class, fn ($notification) => $notification->code === 'ABC123');
    }
}
