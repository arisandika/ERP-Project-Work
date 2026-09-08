<?php

use App\Models\HR\Attendance;
use App\Models\HR\Shift;
use Illuminate\Support\Carbon;

function attendanceWithShift(string $clockIn, string $clockOut, string $startTime = '09:00:00', string $endTime = '17:00:00'): Attendance
{
    $attendance = new Attendance([
        'clock_in' => Carbon::parse($clockIn),
        'clock_out' => Carbon::parse($clockOut),
    ]);

    $attendance->setRelation('shift', new Shift([
        'start_time' => $startTime,
        'end_time' => $endTime,
    ]));

    return $attendance;
}

test('does not count early clock in as overtime', function () {
    $attendance = attendanceWithShift('2026-09-08 08:50:00', '2026-09-08 17:00:00');

    expect($attendance->overtime_minutes)->toBe(0);
});

test('does not count overtime up to 30 minutes after shift end', function () {
    $attendance = attendanceWithShift('2026-09-08 09:00:00', '2026-09-08 17:30:00');

    expect($attendance->overtime_minutes)->toBe(0);
});

test('counts overtime after more than 30 minutes past shift end', function () {
    $attendance = attendanceWithShift('2026-09-08 09:00:00', '2026-09-08 17:31:00');

    expect($attendance->overtime_minutes)->toBe(31);
});

test('handles overnight shifts', function () {
    $attendance = attendanceWithShift(
        '2026-09-08 22:00:00',
        '2026-09-09 06:31:00',
        '22:00:00',
        '06:00:00',
    );

    expect($attendance->overtime_minutes)->toBe(31);
});
