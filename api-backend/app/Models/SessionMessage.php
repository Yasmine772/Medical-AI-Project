<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionMessage extends Model
{
    protected $fillable = [
        'diagnosis_session_id',
        'question_id',
        'selected_option_id',
        'created_at'
    ];

    public function diagnosisSession()
    {
        return $this->belongsTo(DiagnosisSession::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id', 'id');
    }
}
