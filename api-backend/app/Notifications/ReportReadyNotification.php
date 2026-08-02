<?php

namespace App\Notifications;

use App\Models\DiagnosisSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ReportReadyNotification extends Notification
{
    use Queueable;

    protected DiagnosisSession $session;

    public function __construct(DiagnosisSession $session)
    {
        $this->session = $session;
    }

    public function via(object $notifiable): array
    {
        return [FcmChannel::class, 'database'];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $hash = $this->session->session_hash;

        return (new FcmMessage(notification: new FcmNotification(
            title: 'Your medical report is ready',
            body: 'The specialist has reviewed your diagnosis. You can now view and download your report.'
        )))
            ->data([
                'type'         => 'report_ready',
                'session_hash' => $hash,
                'pdf_url'      => $this->session->pdf_url ?? '',
                'url'          => "/reports/{$hash}",
            ])
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
                        'sound' => 'default',
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'        => 'Your medical report is ready',
            'message'      => 'The specialist has reviewed your diagnosis. You can now view and download your report.',
            'type'         => 'report_ready',
            'session_hash' => $this->session->session_hash,
            'pdf_url'      => $this->session->pdf_url ?? '',
            'url'          => "/reports/{$this->session->session_hash}",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
