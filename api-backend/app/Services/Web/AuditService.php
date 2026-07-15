<?php
namespace App\Services\Web;

use App\Models\AuditLog;

class AuditService
{
    /**
     * Get the latest audit logs with optional filters for event and category.
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLatestLogs(array $filters = [])
    {
       return AuditLog::query()
        ->with('user')
        ->latest()
        ->byEvent($filters['event'] ?? null)
        ->byCategory($filters['category'] ?? null)
        ->today()
        ->take(50)
        ->get();
    }
    /**
     * Get the total count of audit logs.
     * @return int
     */

    public function getTotalCount(): int
    {
        return AuditLog::count();
    }
    /**
     * Get the total count of change logs(event:created, updated, deleted).
     * @return int
     */
    public function changeLogs()
    {
        $dataChanges = AuditLog::whereIn('event', ['created', 'updated', 'deleted'])->count();
        return $dataChanges;
    }
}