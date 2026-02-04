<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'share_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function datasets()
    {
        return $this->hasMany(Dataset::class);
    }

    /**
     * Ensure repository has a share token and return it.
     */
    public function ensureShareToken(): string
    {
        if (!empty($this->share_token)) {
            return (string) $this->share_token;
        }

        $this->share_token = (string) Str::uuid();
        $this->save();

        return (string) $this->share_token;
    }
}
