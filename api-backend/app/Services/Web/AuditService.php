<?php
namespace App\Services\Web;

use OwenIt\Auditing\Models\Audit;

class AuditService
{
    /**
     * Get the latest audit logs.
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLatestLogs(int $limit = 50)
    {
        return Audit::with('user')->latest()->take($limit)->get();
    }
    /**
     * Get the total count of audit logs.
     * @return int
     */

    public function getTotalCount(): int
    {
        return Audit::count();
    }
    /**
     * Get the total count of change logs(event:created, updated, deleted).
     * @return int
     */
    public function changeLogs()
    {
        $dataChanges = Audit::whereIn('event', ['created', 'updated', 'deleted'])->count();
        return $dataChanges;
    }
}