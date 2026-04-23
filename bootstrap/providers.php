<?php

use App\Providers\AppServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;

return [
    AppServiceProvider::class,
    TelescopeServiceProvider::class,
    IdeHelperServiceProvider::class,
];
