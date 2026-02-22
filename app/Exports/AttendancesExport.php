<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AttendancesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $attendances;
    protected $selectedColumns;
    protected $availableColumns = [
        'employee_name' => 'Nama Karyawan',
        'date' => 'Tanggal',
        'shift' => 'Shift',
        'note' => 'Catatan',
        'clock_in' => 'Jam Masuk',
        'latitude_in' => 'Latitude Masuk',
        'longitude_in' => 'Longitude Masuk',
        'clock_out' => 'Jam Keluar',
        'latitude_out' => 'Latitude Keluar',
        'longitude_out' => 'Longitude Keluar',
        'status' => 'Status Kehadiran',
        'created_at' => 'Dibuat Pada',
    ];

    public function __construct(Collection $attendances, array $selectedColumns)
    {
        $this->attendances = $attendances;
        $this->selectedColumns = $selectedColumns;
    }

    public function collection()
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $column) {
            if (isset($this->availableColumns[$column])) {
                $headings[] = $this->availableColumns[$column];
            }
        }
        return $headings;
    }

    public function map($attendance): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            switch ($column) {

                case 'employee_name':
                    $row[] = $attendance->employee?->full_name ?? '-';
                    break;

                case 'date':
                    $row[] = $attendance->date?->format('Y-m-d') ?? '-';
                    break;

                case 'shift':
                    $row[] = $attendance->shift
                        ? $attendance->shift->name .
                        ' (' . $attendance->shift->start_time .
                        ' - ' . $attendance->shift->end_time . ')'
                        : '-';
                    break;

                case 'note':
                    $row[] = $attendance->note ?? '-';
                    break;

                case 'clock_in':
                    $row[] = $attendance->clock_in
                        ? $attendance->clock_in->format('H:i:s')
                        : '-';
                    break;

                case 'latitude_in':
                    $row[] = $attendance->latitude_in ?? '-';
                    break;

                case 'longitude_in':
                    $row[] = $attendance->longitude_in ?? '-';
                    break;

                case 'clock_out':
                    $row[] = $attendance->clock_out
                        ? $attendance->clock_out->format('H:i:s')
                        : '-';
                    break;

                case 'latitude_out':
                    $row[] = $attendance->latitude_out ?? '-';
                    break;

                case 'longitude_out':
                    $row[] = $attendance->longitude_out ?? '-';
                    break;

                case 'status':
                    $row[] = ucfirst($attendance->status ?? '-');
                    break;

                case 'created_at':
                    $row[] = $attendance->created_at
                        ? $attendance->created_at->format('Y-m-d H:i:s')
                        : '-';
                    break;

                default:
                    $row[] = '';
                    break;
            }
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF366092'],
                ],
            ],
        ];
    }
}