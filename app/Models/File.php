<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'file_name',
        'file_type',
        'file_path',
        'file_size',
    ];

    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }
}

