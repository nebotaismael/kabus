<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AntiPhishingSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anti_phishing_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key with config fallback.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if not found (falls back to config)
     * @return mixed
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if ($setting) {
            return static::castValue($key, $setting->value);
        }

        // Fall back to config value if no database setting exists
        $configValue = config("antiphishing.{$key}");
        
        return $configValue !== null ? $configValue : $default;
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key The setting key
     * @param mixed $value The value to store
     * @return static
     */
    public static function setValue(string $key, mixed $value): static
    {
        // Convert boolean to string representation
        $stringValue = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue]
        );
    }

    /**
     * Get all settings as an associative array with config fallbacks.
     *
     * @return array<string, mixed>
     */
    public static function getAllSettings(): array
    {
        $keys = ['enabled', 'onion_address', 'difficulty', 'time_limit'];
        $settings = [];

        foreach ($keys as $key) {
            $settings[$key] = static::getValue($key);
        }

        return $settings;
    }

    /**
     * Cast a value to the appropriate type based on the key.
     *
     * @param string $key The setting key
     * @param string $value The raw string value
     * @return mixed
     */
    protected static function castValue(string $key, string $value): mixed
    {
        return match ($key) {
            'enabled' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'difficulty', 'time_limit' => (int) $value,
            default => $value,
        };
    }

    /**
     * Check if a setting exists in the database.
     *
     * @param string $key The setting key
     * @return bool
     */
    public static function hasKey(string $key): bool
    {
        return static::where('key', $key)->exists();
    }
}
