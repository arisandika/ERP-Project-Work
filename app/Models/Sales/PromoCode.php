<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use SoftDeletes;
    protected $table = 'nx_promo_codes';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Scope untuk mencari promo yang valid secara query
     * Cara pakai: PromoCode::available()->get();
     */
    public function scopeAvailable(Builder $query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                  ->orWhereColumn('times_used', '<', 'usage_limit');
            });
    }

    /**
     * Helper untuk cek validitas satu instance object
     * Cara pakai: if($promo->isValid()) { ... }
     */
    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->start_date && $this->start_date > now()) return false;
        if ($this->end_date && $this->end_date < now()) return false;

        // Cek kuota
        if (!is_null($this->usage_limit) && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Relasi ke Quotation
     * Biar bisa cek: $promo->quotations->count()
     */
    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'promo_code_id');
    }
}
