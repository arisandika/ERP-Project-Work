<?php

namespace App\Models\SalesActivity;

use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitRecord extends Model
{
    use SoftDeletes;

    protected $table = 'nx_visit_records';

    protected $fillable = [
        'nx_visit_assignment_id',
        'visited_at',
        'check_in_at',
        'check_out_at',
        'duration_minutes',
        'latitude',
        'longitude',
        'location_address',
        'visit_purpose',
        'nx_project_id',
        'description',
        'visit_result',
        'next_followup_date',
        'followup_notes',
        'internal_note',
        'visit_order',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'duration_minutes' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'next_followup_date' => 'date',
        'visit_order' => 'integer',
    ];

    //----------------------------------------------------------------------
    // Constants
    //----------------------------------------------------------------------

    const RESULT_PENDING = 'pending';
    const RESULT_INTERESTED = 'interested';
    const RESULT_NEED_FOLLOWUP = 'need_followup';
    const RESULT_NOT_INTERESTED = 'not_interested';
    const RESULT_PROGRESSED = 'deal_progressed';
    const RESULT_FAILED = 'failed';

    public static function resultOptions(): array
    {
        return [
            self::RESULT_PENDING => 'Belum Ada Hasil',
            self::RESULT_INTERESTED => 'Tertarik',
            self::RESULT_NEED_FOLLOWUP => 'Perlu Follow Up',
            self::RESULT_NOT_INTERESTED => 'Tidak Tertarik',
            self::RESULT_PROGRESSED => 'Deal Maju',
            self::RESULT_FAILED => 'Gagal',
        ];
    }

    /**
     * Warna badge untuk tiap result — dipakai di Filament & blade.
     */
    public static function resultColors(): array
    {
        return [
            self::RESULT_PENDING => 'gray',
            self::RESULT_INTERESTED => 'success',
            self::RESULT_NEED_FOLLOWUP => 'warning',
            self::RESULT_NOT_INTERESTED => 'danger',
            self::RESULT_PROGRESSED => 'success',
            self::RESULT_FAILED => 'danger',
        ];
    }

    //----------------------------------------------------------------------
    // Relations
    //----------------------------------------------------------------------

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VisitAssignment::class, 'nx_visit_assignment_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'nx_project_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(VisitPhoto::class, 'nx_visit_record_id')
            ->orderBy('taken_at');
    }

    //----------------------------------------------------------------------
    // Helpers
    //----------------------------------------------------------------------

    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function googleMapsUrl(): ?string
    {
        if (!$this->hasCoordinates())
            return null;

        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Hitung jarak (km) ke titik koordinat lain menggunakan formula Haversine.
     */
    public function distanceTo(float $lat, float $lng): ?float
    {
        if (!$this->hasCoordinates())
            return null;

        $earthRadius = 6371;
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($this->latitude))
            * cos(deg2rad($lat))
            * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Hitung durasi (menit) antara record ini dan record sebelumnya dalam assignment.
     */
    public function durationFromPrevious(): ?int
    {
        $previous = VisitRecord::where('nx_visit_assignment_id', $this->nx_visit_assignment_id)
            ->where('visit_order', $this->visit_order - 1)
            ->first();

        if (!$previous)
            return null;

        return (int) $previous->visited_at->diffInMinutes($this->visited_at);
    }

    //----------------------------------------------------------------------
    // Check-in / Checkout Helpers
    //----------------------------------------------------------------------

    /**
     * Apakah record ini sedang dalam status check-in (belum checkout).
     */
    public function isCheckedIn(): bool
    {
        return !is_null($this->check_in_at) && is_null($this->check_out_at);
    }

    /**
     * Apakah record ini sudah selesai (check-out dilakukan).
     */
    public function isCheckedOut(): bool
    {
        return !is_null($this->check_out_at);
    }

    /**
     * Durasi Kunjungan dalam menit (check-in -> check-out).
     */
    public function durationMinutes(): ?int
    {
        if (!$this->check_in_at || !$this->check_out_at)
            return null;

        return (int) $this->check_in_at->diffInMinutes($this->check_out_at);
    }

    //----------------------------------------------------------------------
    // Lifecycle Hooks
    //----------------------------------------------------------------------

    protected static function booted(): void
    {
        // Auto-generate visit_order sebelum insert
        static::creating(function (VisitRecord $record) {
            if (!$record->visit_order) {
                $record->visit_order = (VisitRecord::where('nx_visit_assignment_id', $record->nx_visit_assignment_id)
                    ->max('visit_order') ?? 0) + 1;
            }

            // Set visited_at ke sekarang jika tidak diisi
            if (!$record->visited_at) {
                $record->visited_at = now();
            }
        });

        // Sync status assignment ke in_progress saat record pertama dibuat
        static::created(function (VisitRecord $record) {
            $assignment = $record->assignment;

            if ($assignment && $assignment->status === VisitAssignment::STATUS_PENDING) {
                $assignment->update(['status' => VisitAssignment::STATUS_IN_PROGRESS]);
            }
        });

        // Auto-compute duration_minutes + auto-complete assignment ketika checkout
        static::updated(function (VisitRecord $record) {
            if ($record->isCheckedOut() && is_null($record->getOriginal('check_out_at'))) {
                // Hitung duration jika belum diisi
                if (is_null($record->duration_minutes)) {
                    $record->duration_minutes = $record->durationMinutes();
                    $record->saveQuietly();
                }
            }
        });

        // Cascade delete foto
        static::deleting(function (VisitRecord $record) {
            // VisitPhoto tidak punya SoftDeletes, jadi langsung forceDelete
            $record->photos()->each->delete();
        });
    }
}