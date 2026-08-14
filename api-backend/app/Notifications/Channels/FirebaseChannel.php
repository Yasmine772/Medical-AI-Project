<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toFirebase')) {
            return;
        }

        $data = $notification->toFirebase($notifiable);
        $token = $notifiable->fcm_token ?? null;

        if (!$token) {
            Log::warning('FCM Token not found for user: ' . $notifiable->id);
            return;
        }

        $message = CloudMessage::fromArray([
            'token' => $token,
            'notification' => [
                'title' => $data['title'],
                'body' => $data['body'],
            ],
            'data' => $data['data'] ?? [],
        ]);

        Firebase::messaging()->send($message);
    }
}
