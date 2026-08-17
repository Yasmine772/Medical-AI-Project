<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $message;
    public $data;

    public function __construct($userId, $message, $data = [])
    {
        $this->userId = $userId;
        $this->message = $message;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'notification.received';
    }

    public function broadcastWith()
    {
        $payload = is_array($this->data) ? $this->data : [];

        return [
            'title' => $payload['title'] ?? ($this->message ?: 'New Notification'),
            'body' => $payload['body'] ?? (is_string($this->message) ? $this->message : ''),
            'message' => $this->message,
            'data' => $payload,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
