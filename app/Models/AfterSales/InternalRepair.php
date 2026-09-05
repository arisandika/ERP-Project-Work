<?php

namespace App\Models\AfterSales;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InternalRepair extends Model
{
    protected $table = 'nx_internal_repairs';

    protected $guarded = [];

    protected $fillable = [
        'rma_id',
        'technician_user_id',
        'status',
        'resolution_type',
        'notes',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING    = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED  = 'completed';

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING     => 'Menunggu Dikerjakan',
            self::STATUS_IN_PROGRESS => 'Sedang Dikerjakan',
            self::STATUS_COMPLETED   => 'Selesai',
        ];
    }

    public function rma()
    {
        return $this->belongsTo(ReturnRequest::class, 'rma_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }
}