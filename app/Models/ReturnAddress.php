<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'address',
    ];

    /**
     * Get the user that owns the return address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the currency configuration for this address.
     */
    public function getCurrencyConfig(): ?array
    {
        $currencies = config('nowpayments.supported_currencies', []);
        return $currencies[$this->currency] ?? null;
    }

    /**
     * Get the display name for the currency.
     */
    public function getCurrencyNameAttribute(): string
    {
        $config = $this->getCurrencyConfig();
        return $config['name'] ?? strtoupper($this->currency);
    }

    /**
     * Get the symbol for the currency.
     */
    public function getCurrencySymbolAttribute(): string
    {
        $config = $this->getCurrencyConfig();
        return $config['symbol'] ?? strtoupper($this->currency);
    }

    /**
     * Scope to filter by currency.
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', strtolower($currency));
    }
}
