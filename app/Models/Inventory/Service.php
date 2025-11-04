<?php
namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Untuk Polymorphic

class Service extends Model
{
    use HasFactory;

    protected $table = 'nx_services';

    protected $fillable = [
        'service_name',
        'category_id',
        'price',
    ];

    // Relasi BelongsTo: Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
