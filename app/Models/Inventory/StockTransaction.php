<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Events\Created;
use Illuminate\Database\Eloquent\Events\Updated;
use Illuminate\Database\Eloquent\Events\Deleted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransaction extends Model
{
    use HasFactory;

    protected $table = 'nx_stock_transactions';

    protected $fillable = [
        'product_id',
        'transaction_code',
        'no_reference',
        'warehouse_id',
        'transaction_date',
        'type',
        'quantity',
        'price',
        'total_price',
        'stock_before',
        'stock_after',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::creating(function (StockTransaction $transaction) {

            if ($transaction->quantity <= 0) {
                throw new \Exception('Jumlah transaksi tidak valid.');
            }

            if ($transaction->transaction_date) {
                $transaction->transaction_date = Carbon::parse($transaction->transaction_date)
                    ->setTimeFromTimeString(now()->format('H:i:s'));
            }

            // Kunci jenis transaksi
            $transaction->type = 'masuk';

            $product = Product::findOrFail($transaction->product_id);

            // Harga beli snapshot
            $transaction->price = $product->purchase_price;
            $transaction->total_price = $transaction->price * $transaction->quantity;

            $transaction->created_by = Auth::id();

            DB::transaction(function () use ($transaction) {

                $productStock = ProductStock::firstOrCreate(
                    [
                        'product_id' => $transaction->product_id,
                        'warehouse_id' => $transaction->warehouse_id,
                    ],
                    [
                        'qty' => 0,
                        'status' => 'out_of_stock',
                    ]
                );

                // Audit Stock
                $transaction->stock_before = $productStock->qty;
                $transaction->stock_after = $productStock->qty + $transaction->quantity;

                // Update Stock
                $productStock->qty = $transaction->stock_after;
                $productStock->status = $productStock->qty > 0 ? 'available' : 'out_of_stock';
                $productStock->save();
            });
        });

        static::updating(function () {
            throw new \Exception('Transaksi Stock tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new \Exception('Transaksi Stock tidak boleh dihapus.');
        });
    }
}

