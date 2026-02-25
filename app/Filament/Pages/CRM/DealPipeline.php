<?php

namespace App\Filament\Pages\CRM;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class DealPipeline extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static string $view = 'filament.pages.crm.deal-pipeline';

    protected static ?string $slug = 'crm/deal-pipeline';

    protected static string $routePath = 'crm/deal-pipeline';

    protected static ?string $navigationGroup = 'Manajemen CRM';

    protected static ?string $navigationLabel = 'Deal Pipeline';

    protected static ?string $title = 'Deal Pipeline';

    protected ?string $subheading = 'Board Kanban untuk kelola stage deal';

    protected static ?int $navigationSort = 3;

    public array $sortOrders = [];

    public function mount(): void
    {
        // Inisialisasi
    }

    #[Computed()]
    public function dealStages(): Collection
    {
        // Mengambil semua Deal Stage beserta Deal di dalamnya
        $stages = DealStage::with([
            'deals' => function ($query) {
                // Eager load customer dan lead untuk menampilkan nama di card
                $query->whereNull('deleted_at')

                    ->whereHas('lead', function ($q) {
                    $q->whereNull('deleted_at');
                })

                    ->with(['customer', 'lead', 'quotations'])

                    ->select(
                        'id',
                        'nx_customer_id',
                        'nx_lead_id',
                        'nx_deal_stage_id',
                        'deal_number',
                        'deal_date',
                        'estimated_value',
                        'status',
                        'close_date',
                        'created_at',
                        'updated_at'
                    )
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');
            }
        ])
            ->orderBy('order')
            ->get();

        // Terapkan sorting dinamis untuk setiap kolom (jika user memilih opsi urutkan)
        $stages->each(function ($stage) {
            $sortOrder = $this->sortOrders[$stage->id] ?? 'date_created_newest';
            $stage->deals = $this->applySorting($stage->deals, $sortOrder);
        });

        return $stages;
    }

    public function loadDealStages(): void
    {
        // Hapus cache property agar #[Computed] dijalankan ulang pada request berikutnya
        unset($this->dealStages);
    }

    public function setSortOrder($stageId, $sortOrder)
    {
        $this->sortOrders[$stageId] = $sortOrder;
        $this->loadDealStages();
    }

    private function applySorting($deals, $sortOrder)
    {
        switch ($sortOrder) {
            case 'date_created_newest':
                return $deals->values(); // Default sudah urut dari query
            case 'date_created_oldest':
                return $deals->sortBy('created_at')->values();
            case 'value_highest':
                return $deals->sortByDesc('estimated_value')->values();
            case 'value_lowest':
                return $deals->sortBy('estimated_value')->values();
            case 'close_date':
                return $deals->sortBy(function ($deal) {
                    return $deal->close_date ?? '9999-12-31';
                })->values();
            case 'name_alphabetical':
                return $deals->sortBy(function ($deal) {
                    // Mengurutkan berdasarkan nama Customer atau Lead
                    return $deal->customer?->name ?? $deal->lead?->name ?? 'Z';
                })->values();
            default:
                return $deals->values();
        }
    }

    public function moveDeal($dealId, $newStageId): void
    {
        // Pastikan user punya akses pindahkan deal
        if (!$this->canMoveDeals()) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki izin untuk memindahkan deal ini.')
                ->danger()
                ->send();
            return;
        }

        // Ambil deal (eager load stage) & target stage
        $deal = Deal::with('stage', 'quotations')->findOrFail($dealId);
        $targetStage = DealStage::findOrFail($newStageId);

        $stageName = strtolower($targetStage->name);
        $hasQuotation = $deal->quotations()->exists();

        $isWon = str_contains($stageName, 'won');
        $isLost = str_contains($stageName, 'lost');

        // Cari stage patokan (Penawaran)
        $penawaranStage = DealStage::whereRaw(
            'LOWER(name) LIKE ?',
            ['%penawaran%']
        )->first();

        $penawaranProbability = $penawaranStage?->probability ?? 0;

        // VALIDASI 1: TIDAK PUNYA PENAWARAN
        if (!$hasQuotation) {

            // Tidak boleh ke Penawaran atau di atasnya (Kecuali LOST)
            if ($targetStage->probability >= $penawaranProbability && !$isLost) {
                Notification::make()
                    ->title('Gagal Memperbarui Deal')
                    ->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran atau di atasnya.')
                    ->danger()
                    ->send();

                // Refresh UI agar kartu kembali ke kolom asal
                $this->loadDealStages();
                $this->dispatch('deal-updated');
                return;
            }

            // Tidak boleh ke WON
            if ($isWon) {
                Notification::make()
                    ->title('Gagal Memperbarui Deal')
                    ->body('Deal tanpa Penawaran tidak bisa Closed Won.')
                    ->danger()
                    ->send();

                // Refresh UI agar kartu kembali ke kolom asal
                $this->loadDealStages();
                $this->dispatch('deal-updated');
                return;
            }
        }

        // VALIDASI 2: PUNYA PENAWARAN
        if ($hasQuotation) {

            // Jika turun ke bawah Penawaran tapi bukan LOST -> Tolak
            if ($targetStage->probability < $penawaranProbability && !$isLost) {
                Notification::make()
                    ->title('Gagal Memperbarui Deal')
                    ->body('Deal tidak bisa kembali ke bawah stage Penawaran kecuali Closed Lost.')
                    ->warning()
                    ->send();

                // Refresh UI agar kartu kembali ke kolom asal
                $this->loadDealStages();
                $this->dispatch('deal-updated');
                return;
            }
        }

        // LOGIKA OTOMATISASI UPDATE STATUS DEAL
        $oldStatus = $deal->status;
        $newStatus = 'open'; // Default selalu Open kecuali ke Won/Lost

        if ($isWon) {
            $newStatus = 'won';
        } elseif ($isLost) {
            $newStatus = 'lost';
        }

        // ADJUSTMENT TERBARU: Jika dari WON atau LOST ditarik ke stage biasa -> kembali OPEN
        if ($oldStatus === 'won' && !$isWon && !$isLost) {
            $newStatus = 'open';
        }

        if ($oldStatus === 'lost' && !$isWon && !$isLost) {
            $newStatus = 'open';
        }

        $statusChanged = ($oldStatus !== $newStatus);

        // Jika lolos semua validasi, simpan perubahan stage, status, dan close_date
        $deal->nx_deal_stage_id = $newStageId;
        $deal->status = $newStatus;
        $deal->close_date = in_array($newStatus, ['won', 'lost']) ? now() : null;

        // EKSEKUSI SAVE:
        // Panggilan $deal->save() ini akan otomatis memicu `static::updated()` di file app/Models/Deal.php
        // sehingga Quotation-nya akan otomatis berubah jadi "accepted", "rejected", atau "negotiation"
        // beserta "approved_by" dan "approved_at"!!
        $deal->save();

        // Refresh state board untuk menyimpan urutan baru
        $this->loadDealStages();

        // Memicu re-render UI penuh agar data di DOM sinkron
        $this->dispatch('$refresh');

        // NOTIFIKASI DINAMIS BERHASIL
        if ($statusChanged) {
            $statusLabel = strtoupper($newStatus);

            // Sesuaikan warna notifikasi biar lebih interaktif
            $color = match ($newStatus) {
                'won' => 'success',
                'lost' => 'danger', // saya ubah ke danger untuk Lost
                default => 'info',
            };

            Notification::make()
                        ->title('Berhasil Memperbarui Deal')
                        ->body("Status Deal otomatis diperbarui menjadi {$statusLabel}.")
                ->$color()
                    ->send();
        } else {
            Notification::make()
                ->title('Berhasil Memperbarui Deal')
                ->body("Stage berhasil diperbarui menjadi {$targetStage->name}.")
                ->success()
                ->send();
        }
    }

    #[On('refresh-board')]
    public function refreshBoard(): void
    {
        $this->loadDealStages();
        $this->dispatch('deal-updated'); // trigger event ke Alpine JS di blade
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_deal')
                ->label('New Deal')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->visible(fn() => auth()->user()->can('create_c::r::m::deal'))
                ->url(fn() => DealResource::getUrl('create'))
                ->openUrlInNewTab(),

            Action::make('refresh_board')
                ->label('Refresh Board')
                ->icon('heroicon-m-arrow-path')
                ->action('refreshBoard')
                ->color('warning'),
        ];
    }

    public function canMoveDeals(): bool
    {
        return auth()->user()->can('update_c::r::m::deal');
    }
}