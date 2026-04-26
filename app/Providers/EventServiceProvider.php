<?php

namespace App\Providers;

use App\Domain\Auth\Events\UserAuthenticated;
use App\Domain\Auth\Listeners\EnsureAuthenticatedUserHasRole;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserAuthenticated::class => [
            EnsureAuthenticatedUserHasRole::class,
        ],
    ];
}
