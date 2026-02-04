<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'is_public',
        'share_token',
        'download_count',
        'likes_count',
        'name',
        'description',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'download_count' => 'integer',
        'likes_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'dataset_likes')->withTimestamps();
    }

    public function getTotalSizeAttribute(): int
    {
        // If relationship is eager-loaded, sum in-memory to avoid extra queries.
        if ($this->relationLoaded('files')) {
            return (int) ($this->files->sum(fn ($f) => (int) ($f->file_size ?? 0)) ?? 0);
        }

        // Fallback: sum in DB.
        return (int) $this->files()->sum('file_size');
    }

    public function getTotalSizeMbAttribute(): float
    {
        $bytes = (int) ($this->total_size ?? 0);

        if ($bytes <= 0) {
            return 0.0;
        }

        return round($bytes / 1048576, 2);
    }

    public function getTotalSizeHumanAttribute(): string
    {
        $bytes = (int) ($this->total_size ?? 0);

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
