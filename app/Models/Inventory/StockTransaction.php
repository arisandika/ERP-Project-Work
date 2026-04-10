<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'reference_number',
        'mutation_type',
        'warehouse_id',
        'transaction_date',
        'type',
        'quantity',
        'price',
        'total_price',
        'stock_before',
        'stock_after',
        'reference_id',
        'reference_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public static bool $autoUpdateStock = true;

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

            if (!self::$autoUpdateStock) {
                $transaction->created_by = $transaction->created_by ?? Auth::id();
                $transaction->price = $transaction->price ?? 0;
                $transaction->total_price = $transaction->total_price ?? (($transaction->price ?? 0) * ($transaction->quantity ?? 0));
                return;
            }

            if ($transaction->quantity <= 0) {
                throw new \Exception('Jumlah mutasi tidak valid. Harus lebih dari 0.');
            }

            if ($transaction->transaction_date) {
                $transaction->transaction_date = Carbon::parse($transaction->transaction_date)
                    ->setTimeFromTimeString(now()->format('H:i:s'));
            }

            $product = Product::findOrFail($transaction->product_id);
            $transaction->price = $product->purchase_price ?? 0;
            $transaction->total_price = $transaction->price * $transaction->quantity;
            $transaction->created_by = Auth::id();

            if (is_null($transaction->stock_after)) {

                DB::transaction(function () use ($transaction) {

                    $productStock = ProductStock::where('product_id', $transaction->product_id)
                        ->where('warehouse_id', $transaction->warehouse_id)
                        ->lockForUpdate()
                        ->firstOrCreate(
                            ['product_id' => $transaction->product_id, 'warehouse_id' => $transaction->warehouse_id],
                            ['qty_available' => 0, 'qty_reserved' => 0, 'qty_on_delivery' => 0]
                        );

                    $transaction->stock_before = $productStock->qty_available;
                    $qty = $transaction->quantity;

                    switch ($transaction->mutation_type) {
                        case 'stock_in':
                            $productStock->qty_available += $qty;
                            $transaction->type = 'masuk';
                            break;
                        case 'reserve':
                            if ($productStock->qty_available < $qty) throw new \Exception("Stok siap jual tidak cukup!");
                            $productStock->qty_available -= $qty;
                            $productStock->qty_reserved += $qty;
                            $transaction->type = 'keluar';
                            break;
                        case 'delivery':
                            if ($productStock->qty_reserved < $qty) throw new \Exception("Stok reserved tidak cukup!");
                            $productStock->qty_reserved -= $qty;
                            $productStock->qty_on_delivery += $qty;
                            $transaction->type = 'keluar';
                            break;
                        case 'complete':
                            if ($productStock->qty_on_delivery < $qty) throw new \Exception("Stok delivery tidak cukup!");
                            $productStock->qty_on_delivery -= $qty;
                            $transaction->type = 'keluar';
                            break;
                        case 'cancel':
                            if ($productStock->qty_reserved < $qty) throw new \Exception("Stok reserved tidak cukup dibatalkan!");
                            $productStock->qty_reserved -= $qty;
                            $productStock->qty_available += $qty;
                            $transaction->type = 'masuk';
                            break;
                        case 'adjustment_out':
                            if ($productStock->qty_available < $qty) throw new \Exception("Stok fisik tidak cukup!");
                            $productStock->qty_available -= $qty;
                            $transaction->type = 'keluar';
                            break;
                        default:
                            $productStock->qty_available += $qty;
                            $transaction->mutation_type = 'stock_in';
                            $transaction->type = 'masuk';
                            break;
                    }

                    $transaction->stock_after = $productStock->qty_available;
                    $productStock->save();
                });
            }
        });

        static::updating(function () {
            throw new \Exception('Sistem ERP: Riwayat Transaksi Stock tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new \Exception('Sistem ERP: Riwayat Transaksi Stock tidak boleh dihapus.');
        });
    }
}
