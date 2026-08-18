<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Events\NewNotificationEvent;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toFirebase')) {
            return;
        }

        $data = $notification->toFirebase($notifiable);
        $token = $notifiable->fcm_token ?? null;

        try {
            broadcast(new NewNotificationEvent(
                $notifiable->id,
                $data['title'] ?? 'New Notification',
                $data
            ));

            Log::info(' Reverb notification sent to user: ' . $notifiable->id);
        } catch (\Exception $e) {
            Log::warning('Reverb failed: ' . $e->getMessage());
        }

        if (!$token) {
            Log::warning('FCM Token not found for user: ' . $notifiable->id);
            return;
        }

        try {
            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => $data['title'] ?? 'New Notification',
                    'body' => $data['body'] ?? '',
                ],
                'data' => $data['data'] ?? [],
                'webpush' => [
                    'notification' => [
                        'icon' => $data['icon'] ?? '/logo192.png',
                        'click_action' => $data['click_action'] ?? url('/')
                    ],
                    'fcm_options' => [
                        'link' => $data['click_action'] ?? url('/')
                    ]
                ]
            ]);

            $result = Firebase::messaging()->send($message);

            Log::info('FCM notification sent successfully', [
                'user_id' => $notifiable->id
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('FCM Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
