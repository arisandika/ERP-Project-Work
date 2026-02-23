<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopupBanner extends Model
{
    use HasFactory;

    protected $table = 'nx_web_contents';

    protected $fillable = [
        'title',
        'type',
        'image_path',
        'content_text',
        'cta_url',
        'cta_label',
        'start_date',
        'end_date',
        'is_active',
        'click_count',
        'view_count',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    // Scope untuk Frontend: Hanya ambil yang aktif & masuk periode tayang
    public function scopeActiveNow(Builder $query): Builder
    {
        return $query->where('is_active', true)
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }
}
