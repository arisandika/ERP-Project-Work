<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $table = 'nx_categories';
    protected $guarded = ['id']; // Gunakan ID standar

    // Relasi: Category memiliki banyak Products
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // Relasi: Category memiliki banyak Services
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}