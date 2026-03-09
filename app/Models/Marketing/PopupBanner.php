<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PopupBanner extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'nx_web_contents';

    protected $fillable = [
        'title',
        'type',
        'image_path',
        'content_text',
        'cta_url',
        'cta_text',
        'start_date',
        'end_date',
        'open_in_new_tab',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
    ];

    // Scope untuk Frontend: Hanya ambil yang aktif & masuk periode tayang
    public function scopeActiveNow(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
}
