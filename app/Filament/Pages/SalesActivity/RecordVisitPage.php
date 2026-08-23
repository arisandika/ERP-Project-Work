<?php

namespace App\Filament\Pages\SalesActivity;

use App\Models\HR\Employee;
use App\Models\Project\Project;
use App\Models\SalesActivity\VisitAssignment;
use App\Models\SalesActivity\VisitPhoto;
use App\Models\SalesActivity\VisitRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class RecordVisitPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static string $view = 'filament.pages.sales-activity.record-visit';
    protected static bool $shouldRegisterNavigation = false;

    // ── State ────────────────────────────────────────────────────────────
    public int $assignment;
    public ?VisitAssignment $visitAssignment = null;

    public ?VisitRecord $visitRecord = null;

    // Form fields
    public string $description = '';
    public string $visit_purpose = '';
    public ?int $nx_project_id = null;
    public string $internal_note = '';
    public string $visit_result = 'pending';
    public ?string $next_followup_date = null;
    public string $followup_notes = '';

    // GPS
    public ?float $latitude = null;
    public ?float $longitude = null;
    public string $location_address = '';
    public bool $gpsLoading = false;
    public bool $gpsGranted = false;
    public string $gpsError = '';

    // Foto — disimpan sebagai array of base64 dari JS
    public array $capturedPhotos = []; // [['dataUrl' => '...', 'type' => 'documentation', 'caption' => '']]
    public bool $isSaving = false;

    // ── Mount ────────────────────────────────────────────────────────────
    public function mount(int $assignment): void
    {
        $employeeId = Employee::where('user_id', auth()->id())->value('id');

        $this->visitAssignment = VisitAssignment::with(['deal.customer', 'deal.lead', 'visitRecords.photos'])
            ->where('id', $assignment)
            ->where('assigned_to_type', 'employee')
            ->where('assigned_to_id', $employeeId)
            ->whereNotIn('status', [
                VisitAssignment::STATUS_COMPLETED,
                VisitAssignment::STATUS_CANCELLED,
            ])
            ->firstOrFail();

        $this->assignment = $assignment;

        // Load existing open visit record (check-in tanpa checkout)
        $this->visitRecord = $this->visitAssignment->visitRecords()
            ->whereNull('check_out_at')
            ->whereNotNull('check_in_at')
            ->latest('check_in_at')
            ->first();

        if ($this->visitRecord) {
            $this->description = $this->visitRecord->description ?? '';
            $this->visit_purpose = $this->visitRecord->visit_purpose ?? '';
            $this->nx_project_id = $this->visitRecord->nx_project_id;
            $this->internal_note = $this->visitRecord->internal_note ?? '';
            $this->visit_result = $this->visitRecord->visit_result ?? 'pending';
            $this->next_followup_date = $this->visitRecord->next_followup_date ?? null;
            $this->followup_notes = $this->visitRecord->followup_notes ?? '';
            $this->latitude = $this->visitRecord->latitude;
            $this->longitude = $this->visitRecord->longitude;
            $this->location_address = $this->visitRecord->location_address ?? '';
            $this->gpsGranted = $this->visitRecord->hasCoordinates();
        }
    }

    // public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    // {
    //     return route('filament.admin.pages.sales-activity.record-visit', $parameters, $isAbsolute);
    // }

    public static function getRoutePath(): string
    {
        return 'sales-activity/my-visit-tasks/{assignment}/record';
    }

    public function getTitle(): string
    {
        return 'Rekam Kunjungan — ' . ($this->visitAssignment?->deal?->deal_number ?? '');
    }

    // ── Check-in / Checkout Mode Helpers ──────────────────────────────────

    /**
     * Sedang dalam mode check-in (belum pernah check-in sebelumnya).
     */
    public function isCheckInMode(): bool
    {
        return is_null($this->visitRecord);
    }

    /**
     * Sedang dalam mode check-out (sudah check-in, belum checkout).
     */
    public function isCheckOutMode(): bool
    {
        return $this->visitRecord !== null && $this->visitRecord->isCheckedIn();
    }

    /**
     * Waktu check-in Unix timestamp (ms) untuk timer JS.
     */
    public function checkInTime(): ?int
    {
        return $this->visitRecord && $this->visitRecord->check_in_at
            ? $this->visitRecord->check_in_at->getTimestamp() * 1000
            : null;
    }

    // ── Check-in / Checkout Actions ───────────────────────────────────────

    /**
     * Check-in: buat record minimal (hanya dengan GPS jika tersedia).
     */
    public function checkIn(): void
    {
        if (!$this->gpsGranted || is_null($this->latitude) || is_null($this->longitude)) {
            Notification::make()
                ->title('Lokasi GPS wajib diaktifkan sebelum check-in.')
                ->warning()
                ->send();
            return;
        }

        $nextOrder = $this->visitAssignment->nextVisitOrder();

        $this->visitRecord = VisitRecord::create([
            'nx_visit_assignment_id' => $this->assignment,
            'check_in_at' => now(),
            'visited_at' => now(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location_address' => $this->location_address ?: null,
            'visit_purpose' => $this->visit_purpose ?: null,
            'nx_project_id' => $this->nx_project_id,
            'description' => null,
            'visit_result' => 'pending',
            'visit_order' => $nextOrder,
        ]);

        Notification::make()
            ->title('Check-in berhasil!')
            ->success()
            ->send();
    }

    /**
     * Reload halaman setelah check-in agar timer start.
     */
    public function reloadAfterCheckIn(): void
    {
        $this->redirect(request()->fullUrl());
    }

    /**
     * Check-out: update record, simpan form + foto, redirect ke detail.
     */
    public function checkOut(): void
    {
        if (blank($this->description)) {
            Notification::make()->title('Deskripsi kunjungan wajib diisi.')->danger()->send();
            return;
        }

        if (empty($this->capturedPhotos)) {
            Notification::make()->title('Minimal 1 foto kunjungan harus diambil.')->danger()->send();
            return;
        }

        if (!$this->gpsGranted || is_null($this->latitude)) {
            Notification::make()->title('Lokasi GPS wajib diaktifkan.')->danger()->send();
            return;
        }

        $this->isSaving = true;

        try {
            // Update visit record
            $this->visitRecord->update([
                'check_out_at' => now(),
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'location_address' => $this->location_address ?: null,
                'visit_purpose' => $this->visit_purpose ?: null,
                'nx_project_id' => $this->nx_project_id,
                'internal_note' => $this->internal_note ?: null,
                'description' => $this->description,
                'visit_result' => $this->visit_result,
                'next_followup_date' => $this->next_followup_date ?: null,
                'followup_notes' => $this->followup_notes ?: null,
            ]);

            // Auto-compute duration
            $this->visitRecord->duration_minutes = $this->visitRecord->durationMinutes();
            $this->visitRecord->save();

            // Save photos
            foreach ($this->capturedPhotos as $photo) {
                $path = $this->saveBase64Photo($photo['dataUrl'], $this->visitRecord->id);

                if ($path) {
                    VisitPhoto::create([
                        'nx_visit_record_id' => $this->visitRecord->id,
                        'file_path' => $path,
                        'photo_type' => $photo['type'] ?? 'documentation',
                        'caption' => $photo['caption'] ?: null,
                        'latitude' => $photo['lat'] ?? $this->latitude,
                        'longitude' => $photo['lng'] ?? $this->longitude,
                        'taken_at' => now(),
                    ]);
                }
            }

            Notification::make()
                ->title('Check-out berhasil! Kunjungan selesai.')
                ->success()
                ->send();

            $this->redirect(
                MyVisitTaskDetailPage::getUrl(['assignment' => $this->assignment])
            );

        } catch (\Throwable $e) {
            $this->isSaving = false;
            Notification::make()
                ->title('Gagal menyimpan kunjungan.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ── Header actions ───────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(MyVisitTaskDetailPage::getUrl(['assignment' => $this->assignment])),
        ];
    }

    // ── Livewire events dari JS ──────────────────────────────────────────

    /**
     * Dipanggil dari JS setelah berhasil capture foto.
     * $photo = ['dataUrl' => 'data:image/jpeg;base64,...', 'type' => 'documentation', 'caption' => '']
     */
    #[On('photo-captured')]
    public function addPhoto(array $photo): void
    {
        if (count($this->capturedPhotos) >= 10) {
            Notification::make()
                ->title('Maksimal 10 foto per kunjungan.')
                ->warning()
                ->send();
            return;
        }

        $this->capturedPhotos[] = [
            'dataUrl' => $photo['dataUrl'],
            'type' => $photo['type'] ?? 'documentation',
            'caption' => $photo['caption'] ?? '',
            'lat' => $photo['lat'] ?? null,
            'lng' => $photo['lng'] ?? null,
        ];
    }

    public function removePhoto(int $index): void
    {
        array_splice($this->capturedPhotos, $index, 1);
        $this->capturedPhotos = array_values($this->capturedPhotos);
    }

    public function updatePhotoType(int $index, string $type): void
    {
        if (isset($this->capturedPhotos[$index])) {
            $this->capturedPhotos[$index]['type'] = $type;
        }
    }

    public function updatePhotoCaption(int $index, string $caption): void
    {
        if (isset($this->capturedPhotos[$index])) {
            $this->capturedPhotos[$index]['caption'] = $caption;
        }
    }

    /**
     * Dipanggil dari JS setelah GPS didapat.
     */
    #[On('gps-received')]
    public function onGpsReceived(float $lat, float $lng, string $address = ''): void
    {
        $this->latitude = $lat;
        $this->longitude = $lng;
        $this->location_address = $address;
        $this->gpsLoading = false;
        $this->gpsGranted = true;
        $this->gpsError = '';
    }

    #[On('gps-error')]
    public function onGpsError(string $message): void
    {
        $this->gpsLoading = false;
        $this->gpsError = $message;
    }

    // ── Save ─────────────────────────────────────────────────────────────
    public function save(): void
    {
        // Validasi manual
        if (blank($this->description)) {
            Notification::make()->title('Deskripsi kunjungan wajib diisi.')->danger()->send();
            return;
        }

        if (empty($this->capturedPhotos)) {
            Notification::make()->title('Minimal 1 foto kunjungan harus diambil.')->danger()->send();
            return;
        }

        if (!$this->gpsGranted || is_null($this->latitude)) {
            Notification::make()->title('Lokasi GPS wajib diaktifkan sebelum menyimpan.')->danger()->send();
            return;
        }

        $this->isSaving = true;

        try {
            // 1. Hitung visit_order berikutnya
            $nextOrder = $this->visitAssignment->nextVisitOrder();

            // 2. Simpan VisitRecord
            $record = VisitRecord::create([
                'nx_visit_assignment_id' => $this->assignment,
                'visited_at' => now(),
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'location_address' => $this->location_address ?: null,
                'visit_purpose' => $this->visit_purpose ?: null,
                'nx_project_id' => $this->nx_project_id,
                'internal_note' => $this->internal_note ?: null,
                'description' => $this->description,
                'visit_result' => $this->visit_result,
                'next_followup_date' => $this->next_followup_date ?: null,
                'followup_notes' => $this->followup_notes ?: null,
                'visit_order' => $nextOrder,
            ]);

            // 3. Simpan setiap foto
            foreach ($this->capturedPhotos as $photo) {
                $path = $this->saveBase64Photo($photo['dataUrl'], $record->id);

                if ($path) {
                    VisitPhoto::create([
                        'nx_visit_record_id' => $record->id,
                        'file_path' => $path,
                        'photo_type' => $photo['type'] ?? 'documentation',
                        'caption' => $photo['caption'] ?: null,
                        'latitude' => $photo['lat'] ?? $this->latitude,
                        'longitude' => $photo['lng'] ?? $this->longitude,
                        'taken_at' => now(),
                    ]);
                }
            }

            Notification::make()
                ->title('Kunjungan berhasil direkam!')
                ->success()
                ->send();

            // Redirect ke detail halaman
            $this->redirect(
                MyVisitTaskDetailPage::getUrl(['assignment' => $this->assignment])
            );

        } catch (\Throwable $e) {
            $this->isSaving = false;
            Notification::make()
                ->title('Gagal menyimpan kunjungan.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function saveBase64Photo(string $dataUrl, int $recordId): ?string
    {
        // dataUrl format: data:image/jpeg;base64,/9j/4AAQ...
        if (!str_contains($dataUrl, ','))
            return null;

        [, $base64] = explode(',', $dataUrl, 2);
        $decoded = base64_decode($base64);
        if (!$decoded)
            return null;

        $filename = 'visit_photos/' . date('Y/m') . '/record_' . $recordId . '_' . uniqid() . '.jpg';
        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }

    public function getResultOptions(): array
    {
        return VisitRecord::resultOptions();
    }

    public function getPhotoTypeOptions(): array
    {
        return VisitPhoto::typeOptions();
    }

    /**
     * Contextual visit-purpose dropdown.
     * Statik: "Menawarkan Produk/Prospek Baru"
     * Dinamis: aktif project yang login user jadi member — "Sedang menangani Project X"
     */
    public function getPurposeOptions(): array
    {
        $employeeId = Employee::where('user_id', auth()->id())->value('id');

        $projects = Project::whereHas('members', function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId);
        })
            ->whereNull('end_date')
            ->orWhere('end_date', '>=', now()->toDateString())
            ->get();

        $options = [
            'new_prospect' => 'Menawarkan Produk/Prospek Baru',
        ];

        foreach ($projects as $project) {
            $options['project_' . $project->id] = 'Sedang menangani ' . $project->name;
        }

        return $options;
    }

    public function getClientName(): string
    {
        $deal = $this->visitAssignment?->deal;
        return $deal?->customer?->name ?? $deal?->lead?->name ?? 'Unknown';
    }

    public function back(): void
    {
        $this->redirect(
            MyVisitTaskDetailPage::getUrl(['assignment' => $this->assignment])
        );
    }
}