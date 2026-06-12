<?php

namespace App\Providers;

use App\Events\TwoFactorCodeRequested;
use App\Events\UserCreated;
use App\Events\WithdrawalCodeRequested;
use App\Listeners\TwoFactorCodeRequested\SendTwoFactorCodeNotification;
use App\Listeners\UserCreated\LogUserCreated;
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
        ],

        TwoFactorCodeRequested::class => [
            SendTwoFactorCodeNotification::class,
        ],

        WithdrawalCodeRequested::class => [
            SendWithdrawalCodeNotification::class,
        ],
    ];
}
