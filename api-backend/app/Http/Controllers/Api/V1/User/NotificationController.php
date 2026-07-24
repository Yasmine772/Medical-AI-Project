<?php

namespace App\Http\Controllers\Api\V1\User;
use App\Http\Controllers\Controller;
use App\Services\Api\NotificationService;
use App\Models\User;
use App\Traits\ApiResponseTrait;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponseTrait;
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /*
    *this function is used to get all notifications for the authenticated user
    * @return \Illuminate\Http\JsonResponse
    */
    public function index()
    {
         return $this->notificationService->index();
    }

    /**
     * Mark a specific notification as read for the authenticated user.
     * @param $notificationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($notificationId): \Illuminate\Http\JsonResponse
    {
        $this->notificationService->markAsRead($notificationId);
        return response()->json(['message' => 'Notification read successfully'], 200);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(): \Illuminate\Http\JsonResponse
    {
         $this->notificationService->markAllAsRead();
        return $this->successResponse([],'All notifications marked as read',200);
    }

    /**
    * Delete a specific notification for the authenticated user.
    * @param $notificationId
    * @return \Illuminate\Http\JsonResponse
    */
    public function destroy($notificationId): \Illuminate\Http\JsonResponse
    {
        $this->notificationService->destroy($notificationId);
        return $this->successResponse([],'Notification deleted Successfully',200);
    }

    /**
     * Delete all notifications for the authenticated user.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyAll(): \Illuminate\Http\JsonResponse
    {
        $this->notificationService->destroyAll();
        return $this->successResponse([],'All notifications deleted successfully',200);
    }

    /**
     * Count unread notifications for the authenticated user.
     * @return \Illuminate\Http\JsonResponse
     */
    public function countUnreadNotifications(): \Illuminate\Http\JsonResponse
    {
        $response = $this->notificationService->countUnreadNotifications();
        return $this->successResponse(['unreadNotificationNumber'=>$response],'Count of Unread Notifications',200);
    }

    /**
     * Mark a specific notification as unread for the authenticated user.
     * @param $notificationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsUnread($notificationId): \Illuminate\Http\JsonResponse
    {
        $this->notificationService->markAsUnread($notificationId);
        return $this->successResponse([], 'Notification marked as unread successfully', 200);
    }
}
