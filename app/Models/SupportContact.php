<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportContact extends Model
{
    protected $fillable = [
        'service_name',
        'whatsapp_phone',
        'call_phone',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
