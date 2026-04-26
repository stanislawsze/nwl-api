<?php

namespace App\Domain\Discord\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscordUserLinked
{
    use Dispatchable;

    use SerializesModels;

    public function __construct(
        public User $user,
        public ?int $integrationId,
    ) {}
}
