<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionProgress extends Model
{
    use HasFactory;

    protected $table = 'question_progress';

    protected $fillable = [
        'user_id',
        'study_set_id',
        'question_uid',
        'status',
        'wrong_count',
        'last_practiced_at',
    ];

    protected $casts = [
        'wrong_count' => 'integer',
        'last_practiced_at' => 'datetime',
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
