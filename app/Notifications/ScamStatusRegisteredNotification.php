<?php

namespace App\Notifications;

use App\Models\ScamRegistration;
use App\Utilities\Structure;
// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
// use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScamStatusRegisteredNotification extends Notification
{

    public function __construct(
        public ScamRegistration $registration
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $user   = optional($this->registration->causer);
        $amount = $this->registration->scamRegistrationAmount?->amount;

        $data = Structure::notificationData(
            title: 'Scam Registered Successfully',
            message: sprintf(
                '%s registered scam amount %s',
                $user->name ?? 'A user',
                $amount ? '₹' . number_format($amount, 2) : 'N/A'
            )
        );

        $data['type'] = 'fireworks';
        $data['scam_id'] = $this->registration->scam_id;
        $data['registration_id'] = $this->registration->id;

        Log::info('ScamStatusRegisteredNotification stored', [
            'notifiable_id' => $notifiable->id ?? null,
            'registration_id' => $this->registration->id,
        ]);

        return $data;
    }
}
