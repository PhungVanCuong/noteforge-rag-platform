<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'email_verified_at',
        'custom_gemini_key',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'custom_gemini_key', // Do not expose raw encrypted key
    ];

    protected $appends = [
        'has_custom_key',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getHasCustomKeyAttribute(): bool
    {
        return !empty($this->custom_gemini_key);
    }

    public function setCustomGeminiKeyAttribute(?string $value): void
    {
        $this->attributes['custom_gemini_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedGeminiKey(): ?string
    {
        if (empty($this->custom_gemini_key)) {
            return null;
        }
        try {
            return Crypt::decryptString($this->custom_gemini_key);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function notebooks()
    {
        return $this->hasMany(Notebook::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function conversionJobs()
    {
        return $this->hasMany(ConversionJob::class);
    }

    public function studySets()
    {
        return $this->hasMany(StudySet::class);
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
