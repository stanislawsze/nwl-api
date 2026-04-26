<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    TelescopeServiceProvider::class,
    IdeHelperServiceProvider::class,
];
