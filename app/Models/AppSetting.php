<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("app_setting.{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("app_setting.{$key}");
    }

    public static function getPaymentSettings(): array
    {
        return [
            'vodafone_cash_number' => static::get('vodafone_cash_number', ''),
            'bank_name' => static::get('bank_name', ''),
            'bank_account_name' => static::get('bank_account_name', ''),
            'bank_account_number' => static::get('bank_account_number', ''),
        ];
    }

    public static function updatePaymentSettings(array $data): array
    {
        foreach (['vodafone_cash_number', 'bank_name', 'bank_account_name', 'bank_account_number'] as $key) {
            if (array_key_exists($key, $data)) {
                static::set($key, $data[$key]);
            }
        }

        return static::getPaymentSettings();
    }
}
