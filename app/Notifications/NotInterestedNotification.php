<?php

namespace App\Notifications;

use App\Models\Scam;
use App\Models\User;
use App\Services\ScamService;
use App\Utilities\Structure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NotInterestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Scam $scam,
        public User $causer
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        $title = 'A scam status updated to Not Interested';
        $message = sprintf(
            '%s marked scam as Not Interested | %s',
            $this->causer->name ?? 'A user',
            ScamService::getInstance()->getScamTitle($this->scam)
        );

        $data = Structure::notificationData(
            title: $title,
            message: $message
        );

        $data['scam_id'] = $this->scam->id;
        $data['type'] = 'normal';

        return $data;
    }
}
