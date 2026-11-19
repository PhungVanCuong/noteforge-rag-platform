<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudySet extends Model
{
    use HasFactory;

    protected $fillable = [
        'notebook_id',
        'user_id',
        'share_slug',
        'title',
        'categories_json',
        'questions_json',
        'code_exercises_json',
        'is_public',
        'status',
        'progress',
        'status_message',
        'error_message',
    ];

    protected $casts = [
        'categories_json' => 'array',
        'questions_json' => 'array',
        'code_exercises_json' => 'array',
        'is_public' => 'boolean',
        'progress' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->share_slug)) {
                $model->share_slug = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notebook()
    {
        return $this->belongsTo(Notebook::class);
    }

    public function questionProgresses()
    {
        return $this->hasMany(QuestionProgress::class);
    }

    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
