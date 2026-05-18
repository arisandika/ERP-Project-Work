<?php

namespace App\Models\CRM;

use App\Models\CRM\Deal;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Lead extends Model
{
    use SoftDeletes;

    protected $table = 'nx_leads';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'customer_type',
        'source',
        'pic_name',
        'pic_phone',
        'pic_email',
        'pic_position',
        'status',
        'notes',
        'converted_customer_id',
        'created_by',
        'converted_by',
        'converted_at',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    //----------------------------------------------------------------------
    // Constants
    //----------------------------------------------------------------------

    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_QUALIFIED = 'qualified';
    const STATUS_UNQUALIFIED = 'unqualified';
    const STATUS_CONVERTED = 'converted';
    const STATUS_DEAD = 'dead';

    //----------------------------------------------------------------------
    // Relations
    //----------------------------------------------------------------------

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'nx_lead_id');
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'converted_by');
    }

    //----------------------------------------------------------------------
    // Helpers
    //----------------------------------------------------------------------

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED
            && !is_null($this->converted_customer_id);
    }

    //----------------------------------------------------------------------
    // Actions
    //----------------------------------------------------------------------

    /**
     * Konversi Lead ke tabel nx_customers.
     * Mengembalikan Customer yang sudah ada jika lead sudah pernah dikonversi.
     */
    public function convertToCustomer(): Customer
    {
        // 1. Cek apakah lead ini sudah pernah dikonversi sebelumnya
        if ($this->converted_customer_id) {
            $existingCustomer = Customer::find($this->converted_customer_id);

            // "Jangan buatkan si customer ini duplikasi jika sebelumnya sudah ada approve (masih aktif)"
            if ($existingCustomer && $existingCustomer->status !== 'cancelled') {
                return $existingCustomer;
            }
        }

        // 2. Jika belum ada, ATAU customer sebelumnya berstatus "cancelled", 
        // kita buat customer BARU (Duplikasi untuk History) dari data Lead saat ini
        return DB::transaction(function () {
            $customer = Customer::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'customer_type' => $this->customer_type,
                'source' => $this->source,
                'pic_name' => $this->pic_name,
                'pic_phone' => $this->pic_phone,
                'pic_position' => $this->pic_position,
                'status' => 'active', // Pastikan di-set active
            ]);

            // Dapatkan Employee ID
            $employeeId = Employee::where('user_id', auth()->id())->value('id');

            // Update Lead agar mereferensikan Customer TERBARU
            $this->update([
                'converted_customer_id' => $customer->id,
                'status' => self::STATUS_CONVERTED,
                'converted_by' => $employeeId,
                'converted_at' => now(),
            ]);

            // PENTING: Update semua deals yang terkait lead ini agar menunjuk ke customer baru
            $this->deals()->update([
                'nx_customer_id' => $customer->id,
            ]);

            return $customer;
        });
    }

    //----------------------------------------------------------------------
    // Lifecycle Hooks
    //----------------------------------------------------------------------

    protected static function booted(): void
    {
        static::deleting(function (Lead $lead) {
            if ($lead->isForceDeleting()) {
                $lead->deals()->withTrashed()->get()->each->forceDelete();
            } else {
                $lead->deals()->get()->each->delete();
            }
        });

        static::restored(function (Lead $lead) {
            $lead->deals()->onlyTrashed()->get()->each->restore();
        });
    }
}
