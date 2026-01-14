<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SearchTerm extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'term',
        'user_id',
        'ip_address',
        'source',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a search term.
     */
    public static function log(string $term, ?string $userId, ?string $ip, string $source = 'products'): void
    {
        self::create([
            'term' => substr($term, 0, 100),
            'user_id' => $userId,
            'ip_address' => $ip,
            'source' => $source,
        ]);
    }
}
