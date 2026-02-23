<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;

    protected $table = 'nx_sliders';

    protected $fillable = [
        'name',
        'title',
        'description',
        'image_desktop',
        'image_mobile',
        'cta_text',
        'cta_url',
        'open_in_new_tab',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
    ];

    // Default sorting berdasarkan urutan
    protected static function booted()
    {
        static::addGlobalScope('ordered', function (Builder $builder) {
            $builder->orderBy('sort_order', 'asc');
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
