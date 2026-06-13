<?php

namespace Tests\Unit\Listeners;

use App\Events\TransferReceived;
use App\Listeners\TransferReceived\SendTransferReceivedNotification;
use App\Models\User;
use App\Notifications\TransferReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTransferReceivedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_notifies_the_recipient_with_sender_and_amount(): void
    {
        Notification::fake();

        $recipient = User::factory()->create();
        $sender = User::factory()->create();

        (new SendTransferReceivedNotification)->handle(new TransferReceived($recipient, $sender, 400));

        Notification::assertSentTo(
            $recipient,
            TransferReceivedNotification::class,
            fn ($notification) => $notification->senderName === $sender->name && $notification->amount === 400,
        );
        Notification::assertNotSentTo($sender, TransferReceivedNotification::class);
    }
}
