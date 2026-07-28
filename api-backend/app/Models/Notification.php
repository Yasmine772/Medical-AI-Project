<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Notification extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
     

    protected $fillable = [
        'type',
        // 'title',
        // 'message',
        'data',
        'notifiable_type',
        'notifiable_id',
        'read_at',
    ];


    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
}
