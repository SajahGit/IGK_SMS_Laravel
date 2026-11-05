<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'float' => (float) $setting->value,
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : (is_float($value) ? 'float' : 'string'));

        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) || is_int($value) || is_float($value) ? var_export($value, true) : $value, 'type' => $type, 'group' => $group]
        );
    }
}
