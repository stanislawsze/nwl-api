<?php

namespace App\Domain\Auth\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAuthenticated
{
    use Dispatchable;

    use SerializesModels;

    public function __construct(
        public User $user,
        public string $provider,
    ) {}
}
