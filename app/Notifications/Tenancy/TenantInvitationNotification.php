<?php

namespace App\Notifications\Tenancy;

use App\Models\TenantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TenantInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected TenantInvitation $invitation,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantName = (string) $this->invitation->tenant()->value('name');
        $expiresAt = $this->invitation->expires_at;
        $expiresAtText = $expiresAt instanceof Carbon
            ? $expiresAt->toDayDateTimeString()
            : 'an unspecified date';

        return (new MailMessage)
            ->subject('You have been invited to join a workspace')
            ->greeting('Hello,')
            ->line('You have been invited to join the "' . ($tenantName !== '' ? $tenantName : 'workspace') . '" workspace.')
            ->line('Assigned role: ' . $this->invitation->role)
            ->action('Accept Invitation', $this->acceptUrl())
            ->line('This invitation expires on ' . $expiresAtText . '.');
    }

    protected function acceptUrl(): string
    {
        return str_replace('{token}', $this->invitation->token, (string) config('tenancy.invitations.accept_url'));
    }
}
