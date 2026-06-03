<?php

namespace Modules\StaticPage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaticPage extends Model
{
    use HasFactory;

    protected $table = 'static_pages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'slug',
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
