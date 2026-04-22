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
            ->orderBy('sort_order')
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
        // Pastikan user punya akses
        if (!$this->canMoveDeals()) {
            Notification::make()->title('Akses Ditolak')->danger()->send();
            return;
        }

        $deal = Deal::with('quotations')->findOrFail($dealId);
        $targetStage = DealStage::findOrFail($newStageId);

        // --- VALIDASI FAKTUAL BARU ---
        $hasWon = $deal->quotations()->where('is_primary', true)->exists();
        $hasQuotations = $deal->quotations()->exists();
        $allRejected = $hasQuotations && $deal->quotations()->where('status', '!=', 'rejected')->count() === 0;

        // 1. Cek apakah Deal ini sudah terkunci (final)
        if ($hasWon || $allRejected) {
            Notification::make()
                ->title('Aksi Ditolak')
                ->body('Deal ini terkunci karena status penawarannya sudah final (Won/Lost).')
                ->warning()
                ->send();
            $this->loadDealStages(); // Batalkan perpindahan di UI
            return;
        }

        // 2. Cek apakah user mencoba memindahkan ke stage final secara manual
        $stageName = strtolower($targetStage->name);
        $isTargetWon = str_contains($stageName, 'won');
        $isTargetLost = str_contains($stageName, 'lost');

        if ($isTargetWon || $isTargetLost) {
            Notification::make()
                ->title('Aksi Ditolak')
                ->body('Stage Won/Lost hanya bisa diatur secara otomatis melalui status penawaran.')
                ->danger()
                ->send();
            $this->loadDealStages(); // Batalkan perpindahan di UI
            return;
        }
        // --- AKHIR VALIDASI FAKTUAL ---

        // Validasi lama (tetap relevan untuk stage non-final)
        $hasQuotation = $deal->quotations()->exists();
        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first();
        $penawaranProbability = $penawaranStage?->probability ?? 0;

        if (!$hasQuotation && $targetStage->probability >= $penawaranProbability) {
            Notification::make()
                ->title('Gagal Memperbarui Deal')
                ->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran atau di atasnya.')
                ->danger()
                ->send();
            $this->loadDealStages();
            return;
        }

        // --- (Sisanya bisa tetap sama, atau bisa disederhanakan) ---

        // Jika lolos, simpan perubahan stage
        $deal->nx_deal_stage_id = $newStageId;
        // Status akan tetap 'open' atau 'on_hold', tidak akan pernah menjadi 'won'/'lost' dari sini.
        $deal->save();

        $this->loadDealStages();
        $this->dispatch('$refresh');

        Notification::make()
            ->title('Berhasil Memperbarui Deal')
            ->body("Stage berhasil diperbarui menjadi {$targetStage->name}.")
            ->success()
            ->send();
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