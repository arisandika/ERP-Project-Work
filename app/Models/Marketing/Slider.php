<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'nx_sliders';

    protected $fillable = [
        'title',
        'description',
        'image_desktop',
        'image_mobile',
        'cta_text',
        'cta_url',
        'start_date',
        'end_date',
        'open_in_new_tab',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
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
