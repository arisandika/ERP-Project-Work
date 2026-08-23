<?php

namespace App\Filament\Pages\SalesActivity;

use App\Models\HR\Employee;
use App\Models\SalesActivity\VisitAssignment;
use App\Models\SalesActivity\VisitRecord;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class MyVisitTaskDetailPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static string $view = 'filament.pages.sales-activity.my-visit-task-detail';
    protected static bool $shouldRegisterNavigation = false; // tidak muncul di sidebar

    // ── Route parameter ─────────────────────────────────────────────────
    public int $assignment; // assignment ID dari URL

    public ?VisitAssignment $visitAssignment = null;

    // public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    // {
    //     return route('filament.admin.pages.sales-activity.my-visit-task-detail', $parameters, $isAbsolute);
    // }

    public static function getRoutePath(): string
    {
        return 'sales-activity/my-visit-tasks/{assignment}';
    }

    public function mount(int $assignment): void
    {
        $employeeId = Employee::where('user_id', auth()->id())->value('id');

        // Pastikan tugas ini memang milik pegawai yang login
        $this->visitAssignment = VisitAssignment::with([
            'deal.customer',
            'deal.lead',
            'deal.stage',
            'assignedBy',
            'visitRecords.photos',
        ])
            ->where('id', $assignment)
            ->where('assigned_to_type', 'employee')
            ->where('assigned_to_id', $employeeId)
            ->firstOrFail();

        $this->assignment = $assignment;
    }

    public function getTitle(): string
    {
        $dealNumber = $this->visitAssignment?->deal?->deal_number ?? 'Detail Tugas';
        return "Tugas Kunjungan — {$dealNumber}";
    }

    // ── Header actions ───────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(MyVisitTasksPage::getUrl()),

            Action::make('record_visit')
                ->label('Rekam Kunjungan')
                ->icon('heroicon-o-camera')
                ->color('primary')
                ->visible(fn() => !in_array(
                    $this->visitAssignment?->status,
                    [VisitAssignment::STATUS_COMPLETED, VisitAssignment::STATUS_CANCELLED]
                ))
                ->url(fn() => RecordVisitPage::getUrl([
                    'assignment' => $this->assignment,
                ])),
        ];
    }

    // ── Computed helpers untuk blade ─────────────────────────────────────
    public function getDealProperty(): ?\App\Models\CRM\Deal
    {
        return $this->visitAssignment?->deal;
    }

    public function getClientNameProperty(): string
    {
        $deal = $this->visitAssignment?->deal;
        return $deal?->customer?->name ?? $deal?->lead?->name ?? 'Unknown';
    }

    public function getClientPhoneProperty(): ?string
    {
        $deal = $this->visitAssignment?->deal;
        return $deal?->customer?->phone ?? $deal?->lead?->phone ?? null;
    }

    public function getClientEmailProperty(): ?string
    {
        $deal = $this->visitAssignment?->deal;
        return $deal?->customer?->email ?? $deal?->lead?->email ?? null;
    }

    public function getClientAddressProperty(): ?string
    {
        $deal = $this->visitAssignment?->deal;
        return $deal?->customer?->address ?? $deal?->lead?->address ?? null;
    }

    public function getVisitRecordsProperty()
    {
        return $this->visitAssignment?->visitRecords ?? collect();
    }

    /**
     * Record kunjungan yang sedang aktif (check-in tapi belum check-out).
     */
    public function getActiveVisitRecordProperty(): ?VisitRecord
    {
        return $this->visitRecords
            ->where('check_in_at', '!=', null)
            ->where('check_out_at', null)
            ->sortByDesc('check_in_at')
            ->first();
    }

    /**
     * True jika sales sedang dalam check-in (belum check-out) untuk assignment ini.
     */
    public function getIsCheckedInProperty(): bool
    {
        return $this->activeVisitRecord !== null;
    }

    public function getStatusColorProperty(): string
    {
        return match ($this->visitAssignment?->status) {
            'pending' => 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20',
            'in_progress' => 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20',
            'completed' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20',
            'cancelled' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20',
            default => 'text-gray-600 dark:text-gray-400',
        };
    }

    public function getResultColorClass(string $result): string
    {
        return match ($result) {
            'interested' => 'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-900/20',
            'need_followup' => 'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-900/20',
            'not_interested' => 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20',
            'deal_progressed' => 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
            'failed' => 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20',
            default => 'text-gray-500 bg-gray-50 dark:text-gray-400 dark:bg-gray-800',
        };
    }
}