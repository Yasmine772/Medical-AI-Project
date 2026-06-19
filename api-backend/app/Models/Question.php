<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'text',
        'parent_question_id',
    ];

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    //this mean that a question can have a parent question.
    public function parent()
    {
        return $this->belongsTo(Question::class, 'parent_question_id');
    }

    //this means that a question can have many child questions.
    public function children()
    {
        return $this->hasMany(Question::class, 'parent_question_id');
    }
}
