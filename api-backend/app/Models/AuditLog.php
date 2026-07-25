<?php
namespace App\Models;

use OwenIt\Auditing\Models\Audit as BaseAudit;

class AuditLog extends BaseAudit
{
    /**
     * Scope a query to filter audit logs by event.
     */
  public function scopeByEvent($query, $event)
    {
        return $query->when($event, function ($q) use ($event) {
            $q->where('event', $event);
        });
    }

    /**
     * Scope a query to filter audit logs by category (user type).
     */
      public function scopeByCategory($query, $category)
    {
        return $query->when($category, function ($q) use ($category) {
            $q->where('user_type', $category);
        });
    }

    /**
     * Scope a query to filter audit logs created today.
     */
    public function scopeToday($query)
    {
       return $query->whereDate('created_at', today());
    }
}
