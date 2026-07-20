<?php
namespace App\Services\Api;
use App\Models\User;

class NotificationService
{
    /**
     * Get all notifications for the authenticated user.
     */
    public function index()
    {
        return auth()->user()->notifications;
    }
     
    /**
     * Mark a specific notification as read for the authenticated user.
     * @param $notificationId
     * @return bool
     */
    public function markAsRead($notificationId): bool
   {
    $notification = auth()->user()->notifications()->where('id', $notificationId)->first();

    if ($notification) {
        $notification->update(['read_at' => now()]);
        return true;
    }
    
    return false;
    }

    /**
     * Mark all notifications as read for the authenticated user.
     * @return bool
     */
    public function markAllAsRead(): bool
    {
    auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
    return true;
   }

    /**
     * Delete a specific notification for the authenticated user.
     * @param $notificationId
     * @return bool
     */
    public function destroy($notificationId): bool
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);

        if($notification)
        {
            $notification->delete();
            return true;
        }
        else return false;
    }
    /**
     * Delete all notifications for the authenticated user.
     * @return bool
     */
    public function destroyAll(): bool
    {
       return auth()->user()->notifications()->delete();
    }
    /**
     * Count unread notifications for the authenticated user.
     * @return int
     */
    public function countUnreadNotifications(): int
    {
    return auth()->user()->notifications()->whereNull('read_at')->count();
    }

    /**
     * Mark a specific notification as unread for the authenticated user.
     * @param $notificationId
     * @return bool
     */
    public function markAsUnread($notificationId): bool
    {
    $notification = auth()->user()->notifications()->findOrFail($notificationId);

    if ($notification) {
        $notification->update(['read_at' => null]);
        return true;
    }
    
    return false;
    }
}
