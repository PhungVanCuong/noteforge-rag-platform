<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'notebook_id',
        'user_id',
        'file_name',
        'file_path',
        'file_type',
        'total_pages',
        'rag_status',
        'error_message',
    ];

    protected $casts = [
        'total_pages' => 'integer',
    ];

    public function notebook()
    {
        return $this->belongsTo(Notebook::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
