<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany; // Untuk Polymorphic

class Service extends Model
{
    use HasFactory;

    protected $table = 'nx_services';
    protected $primaryKey = 'id_service';
    protected $guarded = ['id_service'];

    // Relasi BelongsTo: Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Relasi Polymorphic: Service bisa ada di banyak package items
    public function packageItems(): MorphMany
    {
        return $this->morphMany(Sales\PackageItem::class, 'item');
    }
}