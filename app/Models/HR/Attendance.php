<?php
namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use SoftDeletes;

    protected $table = 'nx_attendances';

    protected $fillable = [
        'employee_id',
        'shift_id',
        'date',
        'note',
        'clock_in',
        'clock_out',
        'latitude_in',
        'longitude_in',
        'latitude_out',
        'longitude_out',
        'face_snapshot_in',
        'face_snapshot_out',
        'face_verified_in',
        'face_verified_out',
        'face_similarity_in',
        'face_similarity_out',
        'status',
    ];

    protected $casts = [
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
        'date'      => 'date',
        'tolerance' => 'integer',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    // ── Overtime Accessors ──────────────────────────────────────────────────

    /**
     * Hitung durasi lembur dalam menit.
     * Lembur terjadi jika clock_out melewati shift.end_time.
     */
    public function getOvertimeMinutesAttribute(): int
    {
        if (! $this->clock_in || ! $this->clock_out || ! $this->shift) {
            return 0;
        }

        // 1. Durasi kerja aktual, murni dari clock_in ke clock_out
        $workedMinutes = $this->clock_in->diffInMinutes($this->clock_out, false);

        if ($workedMinutes <= 0) {
            return 0;
        }

        // 2. Durasi shift (bukan jam mulai/selesainya, cuma "berapa lama"-nya)
        $shiftStart = Carbon::createFromFormat('H:i:s', $this->shift->start_time);
        $shiftEnd   = Carbon::createFromFormat('H:i:s', $this->shift->end_time);

        // Shift lintas tengah malam (mis. 22:00 - 06:00) → end lebih kecil dari start
        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        $shiftDurationMinutes = $shiftStart->diffInMinutes($shiftEnd);

        // 3. Overtime = kelebihan waktu kerja aktual dari durasi shift
        $overtime = $workedMinutes - $shiftDurationMinutes;

        // Guard anti data anomali (misal data test yang clock_out-nya salah tanggal)
        if ($overtime > 720) { // lebih dari 12 jam dianggap tidak wajar
            return 0;
        }

        return max(0, (int) $overtime);
    }

    /**
     * Format durasi lembur ke string "Xj Ym".
     * Contoh: 150 menit → "2j 30m"
     */
    public function getOvertimeDurationAttribute(): ?string
    {
        $minutes = $this->overtime_minutes;

        if ($minutes <= 0) {
            return null;
        }

        $hours   = intdiv($minutes, 60);
        $mins    = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}j {$mins}m";
        }

        if ($hours > 0) {
            return "{$hours}j";
        }

        return "{$mins}m";
    }
}
