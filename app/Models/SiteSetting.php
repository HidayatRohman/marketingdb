<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get setting value by key
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        // Handle file type settings (logo, favicon)
        if ($setting->type === 'file' && $setting->value) {
            return asset('storage/' . $setting->value);
        }

        return $setting->value;
    }

    /**
     * Set setting value by key
     */
    public static function set($key, $value, $type = 'text', $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings()
    {
        $settings = self::all();
        $result = [];

        foreach ($settings as $setting) {
            if ($setting->type === 'file' && $setting->value) {
                $result[$setting->key] = asset('storage/' . $setting->value);
            } else {
                $result[$setting->key] = $setting->value;
            }
        }

        return $result;
    }
}
