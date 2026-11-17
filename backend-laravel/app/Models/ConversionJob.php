<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversionJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tool_type',
        'input_files',
        'output_path',
        'status',
        'error_message',
    ];

    protected $casts = [
        'input_files' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
