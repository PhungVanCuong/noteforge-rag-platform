<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'study_set_id',
        'score_10',
        'correct_count',
        'total_questions',
        'duration_seconds',
        'answers_detail_json',
    ];

    protected $casts = [
        'score_10' => 'float',
        'correct_count' => 'integer',
        'total_questions' => 'integer',
        'duration_seconds' => 'integer',
        'answers_detail_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studySet()
    {
        return $this->belongsTo(StudySet::class);
    }
}
