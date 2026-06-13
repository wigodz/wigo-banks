<?php

namespace App\Providers;

use App\Events\DepositCompleted;
use App\Events\TransferReceived;
use App\Events\TwoFactorCodeRequested;
use App\Events\UserCreated;
use App\Events\WithdrawalCodeRequested;
use App\Listeners\DepositCompleted\SendDepositCompletedNotification;
use App\Listeners\TransferReceived\SendTransferReceivedNotification;
use App\Listeners\TwoFactorCodeRequested\SendTwoFactorCodeNotification;
use App\Listeners\UserCreated\LogUserCreated;
use App\Listeners\UserCreated\SendWelcomeNotification;
use App\Listeners\WithdrawalCodeRequested\SendWithdrawalCodeNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserCreated::class => [
            LogUserCreated::class,
            SendWelcomeNotification::class,
        ],

        DepositCompleted::class => [
            SendDepositCompletedNotification::class,
        ],

        TransferReceived::class => [
            SendTransferReceivedNotification::class,
        ],

        TwoFactorCodeRequested::class => [
            SendTwoFactorCodeNotification::class,
        ],

        WithdrawalCodeRequested::class => [
            SendWithdrawalCodeNotification::class,
        ],
    ];
}
