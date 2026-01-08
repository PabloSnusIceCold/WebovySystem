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

    public function getSizeHumanAttribute(): string
    {
        $bytes = (int) ($this->file_size ?? 0);

        if ($bytes <= 0) {
            return '0 KB';
        }

        $kb = $bytes / 1024;
        $mb = $bytes / 1048576;

        if ($mb < 1) {
            return rtrim(rtrim(number_format($kb, 2, '.', ''), '0'), '.') . ' KB';
        }

        return rtrim(rtrim(number_format($mb, 2, '.', ''), '0'), '.') . ' MB';
    }
}
