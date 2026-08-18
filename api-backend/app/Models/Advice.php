<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advice extends Model
{
    protected $fillable = [
        'title',
        'content',
        'category'
    ];

    protected $table = 'advices';

    public function diseases()
    {
        return $this->belongsToMany(Disease::class, 'disease_advice', 'advice_id', 'disease_id');
    }
}
