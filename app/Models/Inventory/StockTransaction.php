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
        'warehouse_id',
        'serial_number_id',
        'transaction_code',
        'reference_number',
        'mutation_type',
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
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
    ];

    public static bool $autoUpdateStock = true;

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class, 'serial_number_id');
    }

    protected static function booted(): void
    {
        static::creating(function (StockTransaction $transaction) {
            $transaction->created_by = $transaction->created_by ?? Auth::id();
            $transaction->price = (float) ($transaction->price ?? 0);
            $transaction->quantity = (int) ($transaction->quantity ?? 0);
            $transaction->total_price = $transaction->total_price ?? ($transaction->price * $transaction->quantity);

            if ($transaction->quantity <= 0) {
                throw new \Exception('Jumlah mutasi tidak valid. Harus lebih dari 0.');
            }

            if ($transaction->transaction_date) {
                $transaction->transaction_date = Carbon::parse($transaction->transaction_date)
                    ->setTimeFromTimeString(now()->format('H:i:s'));
            } else {
                $transaction->transaction_date = now();
            }

            if (! self::$autoUpdateStock) {
                return;
            }

            if (blank($transaction->product_id) || blank($transaction->warehouse_id)) {
                throw new \Exception('Product dan gudang wajib diisi untuk transaksi stok.');
            }

            DB::transaction(function () use ($transaction) {
                $productStock = ProductStock::query()
                    ->where('product_id', $transaction->product_id)
                    ->where('warehouse_id', $transaction->warehouse_id)
                    ->lockForUpdate()
                    ->firstOrCreate(
                        [
                            'product_id' => $transaction->product_id,
                            'warehouse_id' => $transaction->warehouse_id,
                        ],
                        [
                            'qty_available' => 0,
                            'qty_reserved' => 0,
                            'qty_on_delivery' => 0,
                        ]
                    );

                $qty = (int) $transaction->quantity;
                $mutationType = $transaction->mutation_type ?: 'stock_in';

                $stockBeforeAvailable = (int) $productStock->qty_available;
                $transaction->stock_before = $stockBeforeAvailable;

                switch ($mutationType) {
                    case 'stock_in':
                        $productStock->qty_available += $qty;
                        $transaction->type = 'masuk';
                        break;

                    case 'adjustment_in':
                        $productStock->qty_available += $qty;
                        $transaction->type = 'masuk';
                        break;

                    case 'reserve':
                        if ($productStock->qty_available < $qty) {
                            throw new \Exception('Stok siap jual tidak cukup!');
                        }

                        $productStock->qty_available -= $qty;
                        $productStock->qty_reserved += $qty;
                        $transaction->type = 'keluar';
                        break;

                    case 'delivery':
                        if ($productStock->qty_reserved < $qty) {
                            throw new \Exception('Stok reserved tidak cukup!');
                        }

                        $productStock->qty_reserved -= $qty;
                        $productStock->qty_on_delivery += $qty;
                        $transaction->type = 'keluar';
                        break;

                    case 'complete':
                        if ($productStock->qty_on_delivery < $qty) {
                            throw new \Exception('Stok delivery tidak cukup!');
                        }

                        $productStock->qty_on_delivery -= $qty;
                        $transaction->type = 'keluar';
                        break;

                    case 'cancel':
                        if ($productStock->qty_reserved < $qty) {
                            throw new \Exception('Stok reserved tidak cukup dibatalkan!');
                        }

                        $productStock->qty_reserved -= $qty;
                        $productStock->qty_available += $qty;
                        $transaction->type = 'masuk';
                        break;

                    case 'adjustment_out':
                        if ($productStock->qty_available < $qty) {
                            throw new \Exception('Stok fisik tidak cukup!');
                        }

                        $productStock->qty_available -= $qty;
                        $transaction->type = 'keluar';
                        break;

                    default:
                        $transaction->mutation_type = 'stock_in';
                        $productStock->qty_available += $qty;
                        $transaction->type = 'masuk';
                        break;
                }

                $productStock->save();

                $transaction->stock_after = (int) $productStock->qty_available;
            });
        });

        static::updating(function () {
            throw new \Exception('Sistem ERP: Riwayat transaksi stok tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new \Exception('Sistem ERP: Riwayat transaksi stok tidak boleh dihapus.');
        });
    }
}
