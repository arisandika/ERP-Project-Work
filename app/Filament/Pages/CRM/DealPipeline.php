<?php
namespace App\Filament\Pages\CRM;

use App\Filament\Resources\CRM\DealResource;
use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\SalesActivity\VisitAssignmentResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use App\Models\CRM\Lead;
use App\Models\HR\Employee;
use App\Models\SalesActivity\VisitAssignment;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class DealPipeline extends Page
{
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;
        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;
        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'crm';
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

    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    public function mount(): void
    {
        // Inisialisasi
    }

    /**
     * OPTIMASI: cache: true mencegah re-komputasi selama satu request/render cycle.
     * unset($this->dealStages) di loadDealStages() akan invalidate cache ini.
     */
    #[Computed(cache: true)]
    public function dealStages(): Collection
    {
        $stages = DealStage::with([
            'deals' => function ($query) {
                $query->with([
                    // OPTIMASI: Lazy eager load hanya kolom yang dibutuhkan untuk relasi
                    'customer:id,name',
                    'lead:id,name',
                    // OPTIMASI: Quotations hanya ambil kolom yang ditampilkan di blade
                    'quotations:id,nx_deal_id,quotation_number,status,grand_total,is_primary',
                ])
                    ->select(
                        'id',
                        'nx_customer_id',
                        'nx_lead_id',
                        'nx_deal_stage_id',
                        'deal_number',
                        'title',
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

        // OPTIMASI: Pre-compute semua data agregat di sini (PHP), bukan di blade loop
        $stages->each(function ($stage) {
            $sortOrder = $this->sortOrders[$stage->id] ?? 'date_created_newest';
            $stage->deals = $this->applySorting($stage->deals, $sortOrder);

            // Pre-compute nilai yang sering dipanggil berulang di blade
            $stage->deals_count = $stage->deals->count();
            $stage->deals_total_value = $stage->deals->sum('estimated_value');
        });

        return $stages;
    }

    /**
     * OPTIMASI: dealStages hanya dengan info stage (tanpa deals) untuk dropdown di card.
     * Dipisah supaya tidak ikut re-render saat deals berubah.
     */
    #[Computed(cache: true)]
    public function dealStagesForSelect(): Collection
    {
        return DealStage::orderBy('sort_order')->get(['id', 'name']);
    }

    public function loadDealStages(): void
    {
        // Invalidate kedua computed property
        unset($this->dealStages);
        unset($this->dealStagesForSelect);
    }

    public function setSortOrder(int|string $stageId, string $sortOrder): void
    {
        $this->sortOrders[$stageId] = $sortOrder;
        $this->loadDealStages();
    }

    private function applySorting(Collection $deals, string $sortOrder): Collection
    {
        return match ($sortOrder) {
            'date_created_oldest' => $deals->sortBy('created_at')->values(),
            'value_highest' => $deals->sortByDesc('estimated_value')->values(),
            'value_lowest' => $deals->sortBy('estimated_value')->values(),
            'close_date' => $deals->sortBy(fn($d) => $d->close_date ?? '9999-12-31')->values(),
            'name_alphabetical' => $deals->sortBy(fn($d) => $d->customer?->name ?? $d->lead?->name ?? 'Z')->values(),
            default => $deals->values(), // date_created_newest (sudah diorder dari query)
        };
    }

    public function moveDeal(int|string $dealId, int|string $newStageId): void
    {
        if (!$this->canMoveDeals()) {
            Notification::make()->title('Akses Ditolak')->danger()->send();
            return;
        }

        // OPTIMASI: Load hanya relasi quotations yang benar-benar dibutuhkan
        $deal = Deal::with(['quotations:id,nx_deal_id,is_primary,status'])->findOrFail($dealId);
        $targetStage = DealStage::findOrFail($newStageId);

        // Gunakan collection yang sudah di-load (tidak query ulang)
        $hasWon = $deal->quotations->where('is_primary', true)->isNotEmpty();
        $hasQuotations = $deal->quotations->isNotEmpty();
        $allRejected = $hasQuotations && $deal->quotations->where('status', '!=', 'rejected')->isEmpty();

        if ($hasWon || $allRejected) {
            Notification::make()
                ->title('Aksi Ditolak')
                ->body('Deal ini terkunci karena status penawarannya sudah final (Won/Lost).')
                ->warning()
                ->send();
            $this->loadDealStages();
            return;
        }

        $stageName = strtolower($targetStage->name);
        $isTargetWon = str_contains($stageName, 'won');
        $isTargetLost = str_contains($stageName, 'lost');

        if ($isTargetWon || $isTargetLost) {
            Notification::make()
                ->title('Aksi Ditolak')
                ->body('Stage Won/Lost hanya bisa diatur secara otomatis melalui status penawaran.')
                ->danger()
                ->send();
            $this->loadDealStages();
            return;
        }

        $hasQuotation = $deal->quotations->isNotEmpty();
        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first(['id', 'probability']);
        $penawaranProbability = $penawaranStage?->probability ?? 0;

        if (!$hasQuotation && $targetStage->probability >= $penawaranProbability && $penawaranProbability > 0) {
            Notification::make()
                ->title('Gagal Memperbarui Deal')
                ->body('Deal tanpa Penawaran tidak bisa ke stage Penawaran atau di atasnya.')
                ->danger()
                ->send();
            $this->loadDealStages();
            return;
        }

        $deal->nx_deal_stage_id = $newStageId;
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
        $this->dispatch('deal-updated');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_deal')
                ->label('New Deal')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->visible(fn() => auth()->user()->can('create_c::r::m::deal'))
                ->model(Deal::class)
                ->form(fn(Form $form): Form => DealResource::form($form))
                ->modalWidth('5xl')
                ->mountUsing(function (Form $form) {
                    $form->fill([
                        'deal_number' => $this->generateDealNumber(),
                        'status' => Deal::STATUS_OPEN,
                        'deal_date' => now()->toDateString(),
                        'created_by' => Employee::where('user_id', auth()->id())->value('id'),
                        'nx_deal_stage_id' => DealStage::where(function ($q) {
                            $q->where('name', 'LIKE', '%Lead Baru%')
                                ->orWhere('sort_order', 1);
                        })->orderBy('sort_order', 'asc')->first()?->id,
                    ]);
                })
                ->action(function (array $data) {
                    $targetStage = DealStage::find($data['nx_deal_stage_id']);

                    if ($targetStage) {
                        $stageName = strtolower($targetStage->name);
                        $isWonStage = str_contains($stageName, 'won');
                        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first(['id', 'probability']);
                        $penawaranProbability = $penawaranStage?->probability ?? 0;

                        if ($targetStage->probability >= $penawaranProbability || $isWonStage) {
                            Notification::make()
                                ->title('Gagal Menambahkan Deal')
                                ->body('Deal baru tidak bisa memiliki stage Penawaran atau di atasnya.')
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    $data['deal_number'] = $this->generateDealNumber();
                    $data['status'] = Deal::STATUS_OPEN;

                    $deal = Deal::create($data);

                    if ($deal->nx_lead_id) {
                        $lead = Lead::find($deal->nx_lead_id);
                        if ($lead && $lead->status === Lead::STATUS_NEW) {
                            $lead->update(['status' => Lead::STATUS_CONTACTED]);
                        }
                    }

                    Notification::make()->title('Deal Berhasil Dibuat')->success()->send();
                    $this->loadDealStages();
                }),

            Action::make('refresh_board')
                ->label('Refresh Board')
                ->icon('heroicon-m-arrow-path')
                ->action('refreshBoard')
                ->color('warning'),
        ];
    }

    private function generateDealNumber(): string
    {
        do {
            $dealNumber = 'DEAL-' . strtoupper(Str::random(4));
        } while (Deal::where('deal_number', $dealNumber)->exists());

        return $dealNumber;
    }

    public function canMoveDeals(): bool
    {
        return auth()->user()->can('update_c::r::m::deal');
    }

    public function manageVisitsAction(): Action
    {
        return Action::make('manageVisits')
            ->modalHeading('Jadwal Kunjungan')
            ->modalWidth('6xl') // Perbesar sedikit untuk tempat tabel
            ->modalSubmitAction(false) // Hilangkan tombol "Submit" bawah (karena tabel punya action sendiri)
            ->modalCancelActionLabel('Tutup')
            // Di sini kita me-render komponen Livewire ke dalam konten modal
            ->modalContent(fn(array $arguments) => new HtmlString(
                Blade::render('@livewire("sales-activity.deal-visit-list", ["dealId" => $dealId])', [
                    'dealId' => $arguments['dealId']
                ])
            ));
    }

    public function manageQuotationsAction(): Action
    {
        return Action::make('manageQuotations')
            ->modalHeading('Daftar Penawaran')
            ->modalWidth('6xl') // Dibuat lebar (7xl) karena form quotation memiliki repeater yang cukup lebar
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->modalContent(fn(array $arguments) => new HtmlString(
                Blade::render('@livewire("sales.deal-quotation-list", ["dealId" => $dealId])', [
                    'dealId' => $arguments['dealId']
                ])
            ));
    }
}