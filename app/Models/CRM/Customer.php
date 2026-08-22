<?php
namespace App\Models\CRM;

use App\Models\AfterSales\ReturnRequest;
use App\Models\Sales\Quotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'nx_customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'customer_type',
        'nik',
        'npwp',
        'pic_name',
        'pic_position',
        'pic_phone',
        'source',
        'status',
    ];

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'nx_customer_id');
    }

    public function quotations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Quotation::class, // Model tujuan
            Deal::class,      // Model perantara
            'nx_customer_id', // Foreign key di tabel perantara (nx_deals)
            'nx_deal_id',     // Foreign key di tabel tujuan (nx_quotations)
            'id',             // Local key di tabel ini (nx_customers)
            'id'              // Local key di tabel perantara (nx_deals)
        );
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRequest::class, 'nx_customer_id');
    }
}
