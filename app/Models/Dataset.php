<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
