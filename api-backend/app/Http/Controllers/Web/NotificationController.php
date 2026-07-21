<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

class NotificationController extends Controller
{
    use ApiResponseTrait;
    public function getNotifications()
    {
        $allNotifications = auth()->user()->notifications;
        return $this->successResponse($allNotifications, 'All Notifications retrieved successfully', 200);
    }
//************************************************* */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);

        if (!$notification) {
            return $this->errorResponse('Error', 'Notification not found', 404);
        }
        $notification->markAsRead();
        return $this->successResponse(null, 'Notification marked as read', 200);
    }
//****************************************************** */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return $this->successResponse(null, 'All notifications marked as read', 200);
    }
}
