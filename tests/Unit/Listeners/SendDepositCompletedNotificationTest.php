<?php

namespace Tests\Unit\Listeners;

use App\Events\DepositCompleted;
use App\Listeners\DepositCompleted\SendDepositCompletedNotification;
use App\Models\User;
use App\Notifications\DepositCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendDepositCompletedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_the_deposit_notification_with_the_amount(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        (new SendDepositCompletedNotification)->handle(new DepositCompleted($user, 1500));

        Notification::assertSentTo(
            $user,
            DepositCompletedNotification::class,
            fn ($notification) => $notification->amount === 1500,
        );
    }
}
