<?php
namespace App\Filament\Resources\HR\AttendanceResource\Pages;

use App\Filament\Resources\HR\AttendanceResource;
use App\Filament\Actions\ExportAttendancesAction;
use App\Exports\AttendancesExport;
use App\Filament\Widgets\HR\AttendanceLeaveListWidget;
use App\Filament\Widgets\HR\AttendanceMapOverview;
use App\Filament\Widgets\HR\AttendanceStatusChart;
use App\Filament\Widgets\HR\AttendanceSummaryOverview;
use Exception;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;
use App\Models\HR\Attendance;
use Filament\Resources\Components\Tab;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    public function getTabs(): array
    {
        $now = now();

        $start3Months = $now->copy()->subMonths(3)->startOfDay();
        $startLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endLastMonth = $now->copy()->subMonth()->endOfMonth();
        $startThisMonth = $now->copy()->startOfMonth();
        $endThisMonth = $now->copy()->endOfMonth();
        $startWeek = $now->copy()->startOfWeek();
        $endWeek = $now->copy()->endOfWeek();
        $today = $now->toDateString();

        $counts = Attendance::query()
            ->selectRaw("
            COUNT(*) as all_count,
            COALESCE(SUM(date >= ?),0) as last_3_month,
            COALESCE(SUM(date BETWEEN ? AND ?),0) as last_month,
            COALESCE(SUM(date BETWEEN ? AND ?),0) as this_month,
            COALESCE(SUM(date BETWEEN ? AND ?),0) as last_week,
            COALESCE(SUM(DATE(date) = ?),0) as today
        ", [
                $start3Months,
                $startLastMonth,
                $endLastMonth,
                $startThisMonth,
                $endThisMonth,
                $startWeek,
                $endWeek,
                $today
            ])
            ->first();

        return [

            'all' => Tab::make('Semua')
                ->badge((int) $counts->all_count),

            'last_month' => Tab::make('Bulan Lalu')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->whereBetween('date', [$startLastMonth, $endLastMonth])
                )
                ->badge((int) $counts->last_month),

            'this_month' => Tab::make('Bulan Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->whereBetween('date', [$startThisMonth, $endThisMonth])
                )
                ->badge((int) $counts->this_month),

            'last_week' => Tab::make('Minggu Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->whereBetween('date', [$startWeek, $endWeek])
                )
                ->badge((int) $counts->last_week),

            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(
                    fn($query) =>
                    $query->whereDate('date', $today)
                )
                ->badge((int) $counts->today),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'today';
    }

    protected function getRecordCount($fromDate): int
    {
        return AttendanceResource::getModel()::query()
            ->where('date', '>=', $fromDate)
            ->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            ExportAttendancesAction::make(),
        ];
    }

    public function exportAttendances(array $data): void
    {
        $selectedColumns = $data['columns'] ?? [];
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;

        if (empty($selectedColumns)) {
            Notification::make()
                ->title('Export Gagal')
                ->body('Pilih minimal satu kolom untuk diekspor')
                ->danger()
                ->send();
            return;
        }

        $query = Attendance::with(['employee', 'shift'])
            ->orderBy('date', 'desc');

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $attendances = $query->get();

        if ($attendances->isEmpty()) {
            Notification::make()
                ->title('Export Gagal')
                ->body('Tidak ada data presensi pada rentang tanggal tersebut')
                ->warning()
                ->send();
            return;
        }

        try {
            // Generate nama file
            $fileName = 'presensi_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            // Proses Export menggunakan Maatwebsite Excel
            $export = new AttendancesExport($attendances, $selectedColumns);
            Excel::store($export, 'exports/' . $fileName, 'public');

            // Dapatkan URL Download
            $downloadUrl = asset('storage/exports/' . $fileName);

            // Trigger download via JavaScript
            $this->js("
                fetch('{$downloadUrl}')
                    .then(response => response.blob())
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = '{$fileName}';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    });
            ");

            Notification::make()
                ->title('Export Berhasil')
                ->body('File Excel presensi sedang diunduh')
                ->success()
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('Export Gagal')
                ->body('Terjadi kesalahan saat export: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceSummaryOverview::class,
            AttendanceLeaveListWidget::class,
            AttendanceStatusChart::class,
            AttendanceMapOverview::class,
        ];
    }
}
