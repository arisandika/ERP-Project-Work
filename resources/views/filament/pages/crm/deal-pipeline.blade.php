<x-filament-panels::page>
    
    <div class="grid flex-1 auto-cols-fr gap-y-6">
        {{-- Mobile swipe hint --}}
        <div class="flex items-center justify-center gap-1 text-xs text-gray-500 md:hidden dark:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Geser ke samping untuk melihat semua</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </div>
    
        <div x-data="{
                draggingDeal: null,
                isTouchDevice: false,
                touchStartX: 0,
                touchStartY: 0,
                scrollStartX: 0,
                columnScrollPositions: {},
    
                moveDealToStage(dealId, stageId) {
                    $wire.call('moveDeal', parseInt(dealId), parseInt(stageId));
                },
    
                saveScrollPositions() {
                    const columns = document.querySelectorAll('.stage-column .overflow-y-auto');
                    columns.forEach((column, index) => {
                        this.columnScrollPositions[index] = column.scrollTop;
                    });
                },
    
                restoreScrollPositions() {
                    const columns = document.querySelectorAll('.stage-column .overflow-y-auto');
                    columns.forEach((column, index) => {
                        if (this.columnScrollPositions[index] !== undefined) {
                            column.scrollTop = this.columnScrollPositions[index];
                        }
                    });
                },
    
                init() {
                    this.$nextTick(() => {
                        this.removeAllEventListeners();
                        this.attachAllEventListeners();
                        this.setupTouchScrolling();
                        this.isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                        this.setupPageVisibilityListener();
                    });
                },
    
                setupPageVisibilityListener() {
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) {
                            this.saveScrollPositions();
                            setTimeout(() => {
                                this.removeAllEventListeners();
                                this.attachAllEventListeners();
                                this.restoreScrollPositions();
                            }, 100);
                        }
                    });
    
                    window.addEventListener('focus', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            this.removeAllEventListeners();
                            this.attachAllEventListeners();
                            this.restoreScrollPositions();
                        }, 100);
                    });
    
                    window.addEventListener('popstate', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            this.removeAllEventListeners();
                            this.attachAllEventListeners();
                            this.restoreScrollPositions();
                        }, 200);
                    });
    
                    document.addEventListener('livewire:navigated', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            this.removeAllEventListeners();
                            this.attachAllEventListeners();
                            this.restoreScrollPositions();
                        }, 300);
                    });
    
                    document.addEventListener('livewire:load', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            this.removeAllEventListeners();
                            this.attachAllEventListeners();
                            this.restoreScrollPositions();
                        }, 100);
                    });
    
                    document.addEventListener('livewire:updated', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            this.removeAllEventListeners();
                            this.attachAllEventListeners();
                            this.restoreScrollPositions();
                        }, 100);
                    });
    
                    window.addEventListener('deal-updated', () => {
                        this.saveScrollPositions();
                        setTimeout(() => {
                            this.removeAllEventListeners();
                            this.attachAllEventListeners();
                            this.restoreScrollPositions();
                        }, 150);
                    });
    
                    setInterval(() => {
                        if (document.visibilityState === 'visible') {
                            this.saveScrollPositions();
                            this.ensureDragDropInitialized();
                            this.restoreScrollPositions();
                        }
                    }, 2000);
                },
    
                ensureDragDropInitialized() {
                    const deals = document.querySelectorAll('.deal-card');
                    let needsReinitialization = false;
    
                    deals.forEach(deal => {
                        if (!deal.getAttribute('draggable') || deal.getAttribute('draggable') !== 'true') {
                            needsReinitialization = true;
                        }
                    });
    
                    if (needsReinitialization && deals.length > 0) {
                        this.removeAllEventListeners();
                        this.attachAllEventListeners();
                    }
                },
    
                setupTouchScrolling() {
                    const container = document.getElementById('board-container');
    
                    container.addEventListener('touchstart', (e) => {
                        this.touchStartX = e.touches[0].clientX;
                        this.touchStartY = e.touches[0].clientY;
                        this.scrollStartX = container.scrollLeft;
                    }, { passive: true });
    
                    container.addEventListener('touchmove', (e) => {
                        if (e.touches.length !== 1) return;
    
                        const touchX = e.touches[0].clientX;
                        const touchY = e.touches[0].clientY;
                        const moveX = this.touchStartX - touchX;
                        const moveY = this.touchStartY - touchY;
    
                        if (Math.abs(moveX) > Math.abs(moveY)) {
                            e.preventDefault();
                            container.scrollLeft = this.scrollStartX + moveX;
                        }
                    }, { passive: false });
                },
    
                removeAllEventListeners() {
                    const deals = document.querySelectorAll('.deal-card');
                    deals.forEach(deal => {
                        deal.removeAttribute('draggable');
                        const newDeal = deal.cloneNode(true);
                        deal.parentNode.replaceChild(newDeal, deal);
                    });
    
                    const columns = document.querySelectorAll('.stage-column');
                    columns.forEach(column => {
                        const newColumn = column.cloneNode(false);
                        while (column.firstChild) {
                            newColumn.appendChild(column.firstChild);
                        }
                        if (column.parentNode) {
                            column.parentNode.replaceChild(newColumn, column);
                        }
                    });
                },
    
                attachAllEventListeners() {
                    @if(!$this->canMoveDeals())
                        return;
                    @endif
    
                    const deals = document.querySelectorAll('.deal-card');
                    deals.forEach(deal => {
                        deal.setAttribute('draggable', true);
    
                        deal.addEventListener('dragstart', (e) => {
                            this.draggingDeal = deal.getAttribute('data-deal-id');
                            deal.classList.add('opacity-50');
                            e.dataTransfer.effectAllowed = 'move';
                        });
    
                        deal.addEventListener('dragend', () => {
                            deal.classList.remove('opacity-50');
                            this.draggingDeal = null;
                        });
    
                        let longPressTimer;
                        let isDragging = false;
                        let originalColumn;
    
                        deal.addEventListener('touchstart', (e) => {
                            if (isDragging) return;
    
                            longPressTimer = setTimeout(() => {
                                originalColumn = deal.closest('.stage-column');
                                this.draggingDeal = deal.getAttribute('data-deal-id');
                                deal.classList.add('opacity-50', 'relative', 'z-30');
                                isDragging = true;
                                deal.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                            }, 500);
                        }, { passive: true });
    
                        deal.addEventListener('touchmove', (e) => {
                            if (!isDragging) {
                                clearTimeout(longPressTimer);
                                return;
                            }
    
                            const touch = e.touches[0];
                            const columns = document.querySelectorAll('.stage-column');
    
                            columns.forEach(column => {
                                const rect = column.getBoundingClientRect();
                                if (touch.clientX >= rect.left &&
                                    touch.clientX <= rect.right &&
                                    touch.clientY >= rect.top &&
                                    touch.clientY <= rect.bottom) {
                                    column.classList.add('bg-main-primary/10', 'dark:bg-main-light/10');
                                } else {
                                    column.classList.remove('bg-main-primary/10', 'dark:bg-main-light/10');
                                }
                            });
                        });
    
                        deal.addEventListener('touchend', (e) => {
                            clearTimeout(longPressTimer);
    
                            if (!isDragging) return;
    
                            isDragging = false;
                            deal.classList.remove('opacity-50', 'relative', 'z-30');
                            deal.style.boxShadow = '';
    
                            const touch = e.changedTouches[0];
                            const columns = document.querySelectorAll('.stage-column');
    
                            let targetColumn = null;
                            columns.forEach(column => {
                                const rect = column.getBoundingClientRect();
                                if (touch.clientX >= rect.left &&
                                    touch.clientX <= rect.right &&
                                    touch.clientY >= rect.top &&
                                    touch.clientY <= rect.bottom) {
                                    targetColumn = column;
                                }
                                column.classList.remove('bg-main-primary/10', 'dark:bg-main-light/10');
                            });
    
                            if (targetColumn && targetColumn !== originalColumn) {
                                const stageId = targetColumn.getAttribute('data-stage-id');
                                const dealId = this.draggingDeal;
    
                                this.moveDealToStage(dealId, stageId);
                            }
    
                            this.draggingDeal = null;
                        });
    
                        deal.addEventListener('touchcancel', () => {
                            clearTimeout(longPressTimer);
                            if (!isDragging) return;
    
                            isDragging = false;
                            deal.classList.remove('opacity-50', 'relative', 'z-30');
                            deal.style.boxShadow = '';
                            this.draggingDeal = null;
    
                            document.querySelectorAll('.stage-column').forEach(column => {
                                column.classList.remove('bg-main-primary/10', 'dark:bg-main-light/10');
                            });
                        });
                    });
    
                    const columns = document.querySelectorAll('.stage-column');
                    columns.forEach(column => {
                        column.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                            column.classList.add('bg-main-primary/10', 'dark:bg-main-light/10');
                        });
    
                        column.addEventListener('dragleave', () => {
                            column.classList.remove('bg-main-primary/10', 'dark:bg-main-light/10');
                        });
    
                        column.addEventListener('drop', (e) => {
                            e.preventDefault();
                            column.classList.remove('bg-main-primary/10', 'dark:bg-main-light/10');
    
                            if (this.draggingDeal) {
                                const stageId = column.getAttribute('data-stage-id');
                                const dealId = this.draggingDeal;
                                this.draggingDeal = null;
                                this.moveDealToStage(dealId, stageId);
                            }
                        });
                    });
                }
            }" x-init="init()" @deal-moved.window="init()" @deal-updated.window="init()" @refresh-board.window="init()"
            wire:key="board-container-pipeline"
            class="no-scrollbar relative overflow-x-auto pb-6 {{ !$this->canMoveDeals() ? 'view-only-mode' : '' }}"
            id="board-container">
    
            {{-- View Only Mode Indicator --}}
            @if(!$this->canMoveDeals())
                <div class="flex justify-center mb-4">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 border rounded-2xl bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-border-dark text-amber-800 dark:text-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span class="text-sm font-medium">View Only Mode</span>
                        <span class="text-xs opacity-75">Kamu bisa melihat deal tapi tidak bisa memindahkannya</span>
                    </div>
                </div>
            @endif
    
            <div class="inline-flex min-w-full gap-4 pb-2">
                @foreach ($this->dealStages as $stage)
                    <div wire:key="stage-column-{{ $stage->id }}"
                        class="stage-column rounded-xl border border-border-light dark:border-border-dark flex flex-col bg-main-light dark:bg-main-dark w-[calc(85vw-2rem)] min-w-[300px] max-w-[380px] h-[700px] sm:w-[calc((100vw-6rem)/2)] sm:h-[750px] lg:w-[calc((100vw-8rem)/3)] lg:h-[800px] xl:w-[calc((100vw-10rem)/4)] xl:h-[850px]"
                        data-stage-id="{{ $stage->id }}">
                        <div
                            class="flex-shrink-0 px-4 py-3 border-b border-border-light bg-secondary-light rounded-t-xl dark:border-border-dark dark:bg-secondary-dark">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="flex items-center gap-2 font-semibold text-black dark:text-white">
                                    <span>{{ $stage->name }}</span>
                                    <span
                                        class="inline-flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs font-medium rounded-full text-main-primary bg-main-primary/10 ring-1 ring-main-primary/30">
                                        {{ $stage->deals->count() }}
                                    </span>
                                </h3>
    
                                <!-- Sort Menu Dropdown -->
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false"
                                        class="p-1 text-gray-400 transition-colors rounded hover:text-gray-600 dark:hover:text-gray-200">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z">
                                            </path>
                                        </svg>
                                    </button>
    
                                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute left-0 z-50 border shadow-lg rounded-2xl border-border-light bg-secondary-light top-8 w-52 dark:bg-secondary-dark dark:border-border-dark"
                                        style="display: none; transform: translateX(-100%);">
                                        <div class="p-2">
                                            <div
                                                class="flex items-center justify-between px-3 py-2 border-b border-border-light dark:border-border-dark">
                                                <span class="text-sm font-medium text-black dark:text-white">Urutkan
                                                    list</span>
                                                <button @click="open = false"
                                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
    
                                            <div class="py-1">
                                                <button wire:click="setSortOrder({{ $stage->id }}, 'date_created_newest')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white hover:bg-accent-light dark:hover:bg-accent-dark">Terbaru
                                                    dibuat</button>
                                                <button wire:click="setSortOrder({{ $stage->id }}, 'date_created_oldest')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white hover:bg-accent-light dark:hover:bg-accent-dark">Terlama
                                                    dibuat</button>
                                                <button wire:click="setSortOrder({{ $stage->id }}, 'value_highest')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white hover:bg-accent-light dark:hover:bg-accent-dark">Nilai
                                                    Tertinggi</button>
                                                <button wire:click="setSortOrder({{ $stage->id }}, 'value_lowest')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white hover:bg-accent-light dark:hover:bg-accent-dark">Nilai
                                                    Terendah</button>
                                                <button wire:click="setSortOrder({{ $stage->id }}, 'name_alphabetical')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white hover:bg-accent-light dark:hover:bg-accent-dark">Nama
                                                    (A–Z)</button>
                                                <button wire:click="setSortOrder({{ $stage->id }}, 'close_date')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white hover:bg-accent-light dark:hover:bg-accent-dark">Tgl
                                                    Target (Close Date)</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            {{-- CRM Feature: Total Estimated Value per Stage --}}
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Rp {{ number_format($stage->deals->sum('estimated_value'), 0, ',', '.') }}
                            </div>
    
                            {{-- Progress Bar Win Probability Indicator (Optional touch) --}}
                            @if($stage->probability)
                                <div class="flex items-center gap-1 mt-2">
                                    <div class="w-full h-1.5 bg-accent-light rounded-full dark:bg-accent-dark">
                                        <div class="h-1.5 rounded-full"
                                            style="width: {{ $stage->probability }}%; background: linear-gradient(to right, rgb(239, 68, 68), rgb(245, 158, 11), rgb(34, 197, 94))[{{ min(100, max(0, $stage->probability)) }}%]); background-color: {{ $stage->probability < 33 ? 'rgb(239, 68, 68)' : ($stage->probability < 66 ? 'rgb(245, 158, 11)' : 'rgb(34, 197, 94)') }};">
                                        </div>
                                    </div>
                                    <span class="text-xs">{{ $stage->probability }}%</span>
                                </div>
                            @endif
                        </div>
    
                        <div class="flex flex-col flex-1 gap-3 p-3 overflow-y-auto no-scrollbar rounded-b-xl"
                            style="max-height: calc(100% - 60px);"
                            x-data="{ visibleDeals: 10, totalDeals: {{ $stage->deals->count() }}, scrollPos: 0 }"
                            x-init="$nextTick(() => { $el.addEventListener('scroll', () => { scrollPos = $el.scrollTop; if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100 && visibleDeals < totalDeals) { visibleDeals = Math.min(visibleDeals + 10, totalDeals); } }); })"
                            x-ref="dealContainer{{ $stage->id }}">
                            @foreach ($stage->deals as $index => $deal)
                                @php
                                    // LOGIKA STYLING CARD DINAMIS
                                    $stageName = strtolower($stage->name);
    
                                    // Default styling
                                    $borderColor = 'border-l-primary-500 dark:border-l-primary-500';
                                    $valueColor = 'text-emerald-600 dark:text-emerald-400';
    
                                    // Kondisi jika Closed Lost
                                    if (str_contains($stageName, 'lost')) {
                                        $borderColor = 'border-l-red-500 dark:border-l-red-500';
                                        $valueColor = 'text-red-600 dark:text-red-400';
                                    }
                                    // Kondisi jika Closed Won
                                    elseif (str_contains($stageName, 'won')) {
                                        $borderColor = 'border-l-emerald-500 dark:border-l-emerald-500';
                                    }
                                    // Kondisi jika masuk stage Penawaran
                                    elseif (str_contains($stageName, 'penawaran')) {
                                        $borderColor = 'border-l-amber-500 dark:border-l-amber-500';
                                    }
                                @endphp
    
                                <div wire:key="deal-{{ $stage->id }}-{{ $deal->id }}"
                                    class="relative p-3 bg-secondary-light border border-l-4 border-border-light rounded-2xl cursor-move deal-card dark:bg-secondary-dark dark:border-border-dark {{ $borderColor }}"
                                    data-deal-id="{{ $deal->id }}" x-show="{{ $index }} < visibleDeals"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100">
                                    
                                    <div class="flex items-center justify-between mb-3">
                                        <span
                                            class="text-xs font-mono font-medium text-gray-600 dark:text-gray-300 px-1.5 py-0.5 bg-accent-light dark:bg-accent-dark rounded truncate">
                                            {{ $deal->deal_number }}
                                        </span>

                                        @php
                                            $dealStatusColor = match (strtolower($deal->status)) {
                                                'won' => 'fi-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success',
                                                'lost' => 'fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger',
                                                default => 'fi-color-gray bg-main-light text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30 fi-color-gray',
                                            };
                                        @endphp
                                        <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 capitalize {{ $dealStatusColor }}">
                                            {{ $deal->status }}
                                        </span>
                                    </div>

                                    <h4 class="mb-1 text-[15px] font-semibold text-black dark:text-white line-clamp-2">
                                        {{ $deal->customer?->name ?? $deal->lead?->name ?? 'Unknown Client' }}
                                    </h4>

                                    <div class="mb-2 text-lg font-bold {{ $valueColor }}">
                                        Rp {{ number_format($deal->estimated_value, 0, ',', '.') }}
                                    </div>

                                    <div class="flex items-center gap-2 mb-3 text-xs whitespace-nowrap">
                                        @php
                                            $start = $deal->deal_date ? \Carbon\Carbon::parse($deal->deal_date) : null;
                                            $end = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : null;
                                        @endphp

                                        @if ($start)
                                            @if ($end)
                                                @php
                                                    if ($start->format('Y') === $end->format('Y')) {
                                                        if ($start->format('m') === $end->format('m')) {
                                                            // Bulan dan Tahun sama
                                                            $dateRange = $start->format('d') . ' - ' . $end->format('d M y');
                                                        } else {
                                                            // Tahun sama, Bulan beda
                                                            $dateRange = $start->format('d M') . ' - ' . $end->format('d M y');
                                                        }
                                                    } else {
                                                        // Tahun beda
                                                        $dateRange = $start->format('d M y') . ' - ' . $end->format('d M y');
                                                    }
                                                @endphp
                                                <span class="inline-flex items-center font-medium text-gray-500 dark:text-gray-400"
                                                    title="Deal Closed">
                                                    <x-heroicon-m-calendar class="w-3.5 h-3.5 mr-1" />
                                                    {{ $dateRange }}
                                                </span>
                                            @else
                                                @php
                                                    $isOverdue = $start->isPast();
                                                @endphp
                                                <span
                                                    class="inline-flex items-center 
                                            {{ $isOverdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400 font-medium' }}"
                                                    title="Deal Still Open">

                                                    <x-heroicon-m-calendar class="w-3.5 h-3.5 mr-1" />
                                                    {{ $start->format('d M y') }} - Open
                                                </span>
                                            @endif
                                        @endif
                                    </div>

                                    <div
                                        class="flex items-center justify-between gap-3 pt-3 mt-4 border-t border-gray-300 dark:border-border-dark">

                                        @if($this->canMoveDeals())
                                            <div class="relative flex-1 min-w-0" x-data="{ open: false }">
                                                <button @click="open = !open" @click.away="open = false" type="button"
                                                    class="flex items-center justify-between w-full px-2.5 py-1.5 text-xs font-medium text-gray-700 transition-all bg-main-light border border-border-light rounded-2xl shadow-sm hover:bg-accent-light dark:bg-accent-dark dark:border-border-dark dark:text-gray-300 dark:hover:bg-secondary-dark">
                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                        <span class="truncate">{{ $stage->name }}</span>
                                                    </div>
                                                    <svg class="flex-shrink-0 w-3.5 h-3.5 ml-1 text-gray-400 transition-transform duration-200"
                                                        :class="{ 'rotate-180': open }" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>

                                                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                    class="absolute left-0 top-full mt-1 z-40 w-full min-w-[150px] overflow-y-auto origin-top bg-secondary-light border border-border-light rounded-2xl shadow-xl dark:bg-secondary-dark dark:border-border-dark max-h-48 no-scrollbar"
                                                    style="display: none;">
                                                    <div class="p-1">
                                                        @foreach($this->dealStages as $stageOption)
                                                            <button type="button"
                                                                wire:click="moveDeal({{ $deal->id }}, {{ $stageOption->id }})"
                                                                @click="open = false" class="flex items-center w-full gap-2 px-3 py-2 text-sm text-left rounded-lg transition-colors group
                                                        {{ $stageOption->id === $stage->id
                                        ? 'bg-main-light text-main-primary dark:bg-accent-dark'
                                        : 'text-gray-700 hover:bg-accent-light dark:text-gray-200 dark:hover:bg-accent-dark' 
                                                        }}">
                                                                <span class="truncate">{{ $stageOption->name }}</span>
                                                                @if($stageOption->id === $stage->id)
                                                                    <svg class="w-3.5 h-3.5 ml-auto text-primary-600 dark:text-primary-400 flex-shrink-0"
                                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                            d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                @endif
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="flex items-center flex-shrink-0 gap-2">
                                            @if($deal->quotations && $deal->quotations->isNotEmpty())
                                                <div class="relative" x-data="{ openQuotations: false }">
                                                    <button @click="openQuotations = !openQuotations"
                                                        @click.away="openQuotations = false"
                                                        class="relative flex items-center justify-center w-8 h-8 transition-colors border rounded-full shadow-sm border-border-light bg-main-light hover:bg-accent-light dark:bg-accent-dark dark:border-border-dark dark:hover:bg-secondary-dark text-amber-600 dark:text-amber-500"
                                                        title="Lihat {{ $deal->quotations->count() }} Penawaran">
                                                        <x-heroicon-o-document-text class="w-4 h-4" />
                                                        <span
                                                            class="absolute top-0 right-0 flex items-center justify-center w-3.5 h-3.5 text-[10px] font-bold text-white bg-red-500 rounded-full transform translate-x-1/4 -translate-y-1/4">
                                                            {{ $deal->quotations->count() }}
                                                        </span>
                                                    </button>

                                                    <div x-show="openQuotations" x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        class="absolute right-[-120%] z-[999] w-64 p-2 mt-2 origin-top-right bg-secondary-light border border-border-light rounded-2xl shadow-xl dark:bg-secondary-dark dark:border-border-dark"
                                                        style="display: none;">
                                                        <div
                                                            class="px-2 py-1.5 mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase border-b dark:text-gray-400 dark:border-border-dark">
                                                            List Penawaran
                                                        </div>

                                                        <div class="flex flex-col gap-1 overflow-y-auto max-h-48 custom-scrollbar">
                                                            @foreach($deal->quotations as $quotation)
                                                                <a href="{{ \App\Filament\Resources\Sales\QuotationResource::getUrl('edit', ['record' => $quotation->id]) }}"
                                                                    target="_blank"
                                                                    class="block px-2 py-2 transition-colors rounded-md hover:bg-accent-light dark:hover:bg-accent-dark">
                                                                    <div class="flex items-center justify-between mb-1">
                                                                        <span
                                                                            class="text-xs font-medium text-black truncate dark:text-white"
                                                                            title="{{ $quotation->quotation_number }}">
                                                                            {{ $quotation->quotation_number }}
                                                                        </span>

                                                                        @php
                                                                            $statusColor = match (strtolower($quotation->status)) {
                                                                                'approved', 'accepted', 'win' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                                                                'rejected', 'lost' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                                                'sent' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                                                default => 'bg-accent-light text-gray-700 dark:bg-accent-dark dark:text-gray-300',
                                                                            };
                                                                        @endphp
                                                                        <span
                                                                            class="text-[9px] font-semibold px-1.5 py-0.5 rounded-md tracking-wider {{ $statusColor }}">
                                                                            {{ $quotation->status }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                                        Rp {{ number_format($quotation->grand_total, 0, ',', '.') }}
                                                                    </div>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <a href="{{ \App\Filament\Resources\CRM\DealResource::getUrl('view', ['record' => $deal->id]) }}"
                                                target="_blank" rel="noopener noreferrer" onclick="
                                    event.preventDefault();
                                    const container = this.closest('.overflow-y-auto');
                                    const scrollPos = container.scrollTop;
                                    window.open(this.href, '_blank');
                                    setTimeout(() => {
                                        container.scrollTop = scrollPos;
                                    }, 0);
                                    return false;
                                " class="flex items-center justify-center w-8 h-8 transition-colors border rounded-full shadow-sm border-border-light bg-main-light hover:bg-accent-light dark:bg-accent-dark dark:border-border-dark dark:hover:bg-secondary-dark text-main-primary"
                                                title="Lihat detail Deal">
                                                <x-heroicon-o-eye class="w-4 h-4" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
    
                            @if ($stage->deals->isEmpty())
                                <div
                                    class="flex items-center justify-center h-24 text-sm italic text-gray-500 border border-gray-300 border-dashed rounded-2xl dark:text-gray-400 dark:border-border-dark">
                                    Tidak ada deal di stage ini
                                </div>
                            @else
                                <div x-show="visibleDeals < totalDeals"
                                    class="flex items-center justify-center py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>Memuat lebih banyak...</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
    
                @if ($this->dealStages->isEmpty())
                    <div
                        class="flex items-center justify-center w-full h-64 text-gray-500 border border-dashed border-border-light bg-secondary-light dark:text-gray-400 dark:bg-main-dark rounded-xl dark:border-border-dark">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                            <p class="text-base font-medium">Belum ada Deal Stage</p>
                            <p class="text-sm">Silakan buat Deal Stage terlebih dahulu melalui menu CRM.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-filament-panels::page>