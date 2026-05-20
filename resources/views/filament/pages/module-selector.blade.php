<x-filament-panels::page>

    <style>
        .fi-header,
        .fi-sidebar,
        .fi-topbar-open-sidebar-btn {
            display: none !important;
        }

        .fi-main {
            width: 100% !important;
            max-width: none !important;
            margin-left: 0 !important;
            margin-inline-start: 0 !important;
            padding: 0 !important;
        }

        .fi-main-ctn,
        .fi-page {
            width: 100% !important;
            max-width: none !important;
            background: transparent !important;
        }

        .fi-topbar {
            left: 0 !important;
            inset-inline-start: 0 !important;
        }

        /* Card pseudo-element effects yang tidak bisa dilakukan Tailwind murni */
        .module-card::before {
            content: "";
            position: absolute;
            top: -32px;
            right: -32px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--card-glow);
            filter: blur(40px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 200ms ease;
        }

        .module-card:hover::before {
            opacity: 0.2;
        }

        .dark .module-card:hover::before {
            opacity: 0.35;
        }

        .module-card::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 16px;
            right: 16px;
            height: 0px;
            border-radius: 999px 999px 0 0;
            background: var(--card-accent);
            opacity: 0;
            transform: scaleX(0.4);
            transition: opacity 200ms ease, transform 200ms ease;
        }

        .module-card:hover::after {
            opacity: 1;
            transform: scaleX(1);
        }

        .module-card:hover .card-arrow {
            background: var(--card-accent) !important;
            border-color: var(--card-accent) !important;
            color: #fff !important;
            transform: translateX(2px);
        }

        .module-card:hover .card-cta {
            color: var(--card-accent) !important;
        }

        .module-card:hover {
            border-color: var(--card-accent) !important;
        }
    </style>

    @php
        $modules = $this->getModules();
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin') ?? false;

        // Ambil data yang sudah diproses di PHP Class
        $infoMessage = $this->attendanceInfo['message'] ?? null;
        $infoType = $this->attendanceInfo['type'] ?? 'info';
        $pill = $this->pillData; // Data untuk kapsul kecil

        // Greeting berdasarkan jam
        $hour = now()->hour;
        $greeting = match (true) {
            $hour >= 5 && $hour < 11 => 'Selamat pagi',
            $hour >= 11 && $hour < 15 => 'Selamat siang',
            $hour >= 15 && $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        $moduleConfig = [
            'attendance' => ['accent' => '#3b82f6', 'glow' => '#3b82f6', 'icon_bg' => 'bg-blue-50 dark:bg-blue-950/40', 'icon_color' => 'text-blue-600 dark:text-blue-400', 'icon_border' => 'ring-blue-200 dark:ring-blue-800', 'tag' => 'bg-blue-50 text-blue-600 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:ring-blue-800'],
            'hr' => ['accent' => '#6366f1', 'glow' => '#6366f1', 'icon_bg' => 'bg-indigo-50 dark:bg-indigo-950/40', 'icon_color' => 'text-indigo-600 dark:text-indigo-400', 'icon_border' => 'ring-indigo-200 dark:ring-indigo-800', 'tag' => 'bg-indigo-50 text-indigo-600 ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:ring-indigo-800'],
            'finance' => ['accent' => '#10b981', 'glow' => '#10b981', 'icon_bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'icon_color' => 'text-emerald-600 dark:text-emerald-400', 'icon_border' => 'ring-emerald-200 dark:ring-emerald-800', 'tag' => 'bg-emerald-50 text-emerald-600 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800'],
            'sales' => ['accent' => '#8b5cf6', 'glow' => '#8b5cf6', 'icon_bg' => 'bg-violet-50 dark:bg-violet-950/40', 'icon_color' => 'text-violet-600 dark:text-violet-400', 'icon_border' => 'ring-violet-200 dark:ring-violet-800', 'tag' => 'bg-violet-50 text-violet-600 ring-violet-200 dark:bg-violet-950/40 dark:text-violet-400 dark:ring-violet-800'],
            'procurement' => ['accent' => '#f59e0b', 'glow' => '#f59e0b', 'icon_bg' => 'bg-amber-50 dark:bg-amber-950/40', 'icon_color' => 'text-amber-600 dark:text-amber-400', 'icon_border' => 'ring-amber-200 dark:ring-amber-800', 'tag' => 'bg-amber-50 text-amber-600 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-800'],
            'inventory' => ['accent' => '#64748b', 'glow' => '#64748b', 'icon_bg' => 'bg-slate-100 dark:bg-slate-800/60', 'icon_color' => 'text-slate-600 dark:text-slate-400', 'icon_border' => 'ring-slate-200 dark:ring-slate-700', 'tag' => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-400 dark:ring-slate-700'],
            'crm' => ['accent' => '#06b6d4', 'glow' => '#06b6d4', 'icon_bg' => 'bg-cyan-50 dark:bg-cyan-950/40', 'icon_color' => 'text-cyan-600 dark:text-cyan-400', 'icon_border' => 'ring-cyan-200 dark:ring-cyan-800', 'tag' => 'bg-cyan-50 text-cyan-600 ring-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-400 dark:ring-cyan-800'],
            'marketing' => ['accent' => '#ec4899', 'glow' => '#ec4899', 'icon_bg' => 'bg-pink-50 dark:bg-pink-950/40', 'icon_color' => 'text-pink-600 dark:text-pink-400', 'icon_border' => 'ring-pink-200 dark:ring-pink-800', 'tag' => 'bg-pink-50 text-pink-600 ring-pink-200 dark:bg-pink-950/40 dark:text-pink-400 dark:ring-pink-800'],
            'system' => ['accent' => '#94a3b8', 'glow' => '#94a3b8', 'icon_bg' => 'bg-slate-100 dark:bg-slate-800/60', 'icon_color' => 'text-slate-500 dark:text-slate-400', 'icon_border' => 'ring-slate-200 dark:ring-slate-700', 'tag' => 'bg-slate-100 text-slate-500 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-400 dark:ring-slate-700'],
        ];
    @endphp

    <div class="flex flex-col items-center min-h-screen px-4 pb-8 sm:px-6 lg:px-8">

        {{-- TOP BAR --}}
        <div class="flex items-center justify-between w-full max-w-6xl pb-6 mb-4">
            {{-- Logo --}}
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/logo-dark.png') }}" alt="Logo" class="h-8 dark:hidden">
                <img src="{{ asset('assets/logo-light.png') }}" alt="Logo" class="hidden h-8 dark:block">
            </div>

            {{-- Jam & Tanggal --}}
            <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400" id="header-date">Memuat...</p>
                <p class="text-base font-bold tracking-tight text-gray-800 tabular-nums dark:text-gray-100"
                    id="header-time">--:--:--</p>
            </div>
        </div>

        {{-- HERO / GREETING --}}
        <div class="w-full max-w-4xl mb-6 text-center">
            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $greeting }}, <span
                    class="font-semibold text-gray-700 dark:text-gray-300">{{ $user?->name ?? 'User' }}</span> 👋
            </p>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                Pilih modul kerja
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Akses semua area sistem dari satu tempat. Klik modul yang ingin kamu buka.
            </p>
        </div>

        {{-- INFO PILLS (Department, Role, Shift, Cuti, Libur) --}}
        @if(!$isSuperAdmin && $user?->employee)
            <div class="flex flex-wrap justify-center w-full gap-2 mb-6 max-w-7xl">

                {{-- Role User --}}
                @if($pill['role'])
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:ring-slate-700">
                        <x-filament::icon icon="heroicon-o-identification" class="w-3.5 h-3.5" /> {{ $pill['role'] }}
                    </span>
                @endif

                {{-- Departemen --}}
                @if($pill['department'])
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-violet-50 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-800">
                        <x-filament::icon icon="heroicon-o-building-office" class="w-3.5 h-3.5" /> {{ $pill['department'] }}
                    </span>
                @endif

                {{-- Status Hari (Kerja / Libur) --}}
                @if($pill['is_weekend'])
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800">
                        <x-filament::icon icon="heroicon-o-sun" class="w-3.5 h-3.5" /> Sedang akhir pekan
                    </span>
                @else
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-blue-50 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-800">
                        <x-filament::icon icon="heroicon-o-briefcase" class="w-3.5 h-3.5" /> Hari kerja aktif
                    </span>
                @endif

                {{-- Shift Kerja --}}
                @if($pill['shift'])
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-800">
                        <x-filament::icon icon="heroicon-o-clock" class="w-3.5 h-3.5" />
                        <strong class="ml-1">{{ $pill['shift'] }}</strong>
                    </span>
                @endif

                {{-- Cuti yang sedang berlangsung atau akan datang --}}
                @if($pill['upcoming_leave'])
                    @php
                        $cStart = \Carbon\Carbon::parse($pill['upcoming_leave']['start']);
                        $cEnd = \Carbon\Carbon::parse($pill['upcoming_leave']['end']);
                        $isNow = $cStart->isToday() || ($cStart->isPast() && $cEnd->isFuture()) || $cEnd->isToday();
                    @endphp
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full ring-1
                                                    {{ $isNow ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-800' }}">
                        <x-filament::icon icon="heroicon-o-paper-airplane" class="w-3.5 h-3.5" />
                        @if($isNow)
                            Sedang cuti s/d {{ $cEnd->translatedFormat('d M') }}
                        @else
                            Cuti: {{ $cStart->translatedFormat('d M') }} - {{ $cEnd->translatedFormat('d M') }}
                        @endif
                    </span>
                @endif

                {{-- Libur Nasional Terdekat --}}
                @if($pill['upcoming_holiday'])
                    @php
                        $hDate = \Carbon\Carbon::parse($pill['upcoming_holiday']['date']);
                        $isToday = $hDate->isToday();
                        $selisih = now()->diffInDays($hDate, false);
                    @endphp
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full ring-1
                                                    {{ $isToday ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' }}">
                        <x-filament::icon icon="heroicon-o-star" class="w-3.5 h-3.5" />
                        @if($isToday)
                            Libur: {{ $pill['upcoming_holiday']['name'] }}
                        @elseif($selisih <= 7)
                            {{ $pill['upcoming_holiday']['name'] }} — {{ $selisih }} hari lagi
                        @else
                            Libur: {{ $hDate->translatedFormat('d M') }} ({{ $pill['upcoming_holiday']['name'] }})
                        @endif
                    </span>
                @endif

            </div>
        @endif

        {{-- STATUS BANNER PRESENSI --}}
        @if(!$isSuperAdmin && $infoMessage)
            @php
                [$bgBanner, $ringBanner, $textBanner] = match ($infoType) {
                    'warning' => ['bg-amber-50 dark:bg-amber-950/30', 'ring-amber-300 dark:ring-amber-800', 'text-amber-800 dark:text-amber-300'],
                    'success' => ['bg-emerald-50 dark:bg-emerald-950/30', 'ring-emerald-300 dark:ring-emerald-800', 'text-emerald-800 dark:text-emerald-300'],
                    'danger' => ['bg-rose-50 dark:bg-rose-950/30', 'ring-rose-300 dark:ring-rose-800', 'text-rose-800 dark:text-rose-300'],
                    default => ['bg-blue-50 dark:bg-blue-950/30', 'ring-blue-300 dark:ring-blue-800', 'text-blue-800 dark:text-blue-300'],
                };
                $icon = match ($infoType) {
                    'warning' => 'heroicon-o-exclamation-triangle',
                    'success' => 'heroicon-o-check-circle',
                    'danger' => 'heroicon-o-x-circle',
                    default => 'heroicon-o-information-circle',
                };
            @endphp
            <div
                class="flex hover:underline-offset-4 items-center w-full max-w-2xl gap-4 p-4 mb-10 shadow-sm rounded-xl ring-1 {{ $bgBanner }} {{ $ringBanner }} {{ $textBanner }}">
                <x-filament::icon :icon="$icon" class="w-5 h-5 shrink-0" />
                <p class="flex-1 text-sm leading-relaxed">{!! $infoMessage !!}</p>
            </div>
        @endif

        {{-- MODULE GRID --}}
        @if($modules->isEmpty())
            <div
                class="flex items-start w-full max-w-6xl gap-4 p-5 rounded-xl ring-1 ring-amber-200 bg-amber-50 dark:ring-amber-800 dark:bg-amber-950/30">
                <div
                    class="flex items-center justify-center rounded-lg w-9 h-9 bg-amber-100 dark:bg-amber-900/40 ring-1 ring-amber-200 dark:ring-amber-800 text-amber-600 dark:text-amber-400 shrink-0">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Belum ada modul yang bisa diakses
                    </p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-700 dark:text-amber-400">
                        Akun kamu belum memiliki permission modul apapun. Hubungi administrator untuk mengatur hak akses.
                    </p>
                </div>
            </div>

        @else
            <div class="grid w-full max-w-6xl grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($modules as $module)
                    @php $cfg = $moduleConfig[$module['key']] ?? $moduleConfig['system']; @endphp

                    <button type="button" wire:click="selectModule('{{ $module['key'] }}')" class="module-card group relative flex flex-col p-4 sm:p-5 text-left rounded-2xl
                                                               ring-1 ring-border-light dark:ring-border-dark
                                                               bg-secondary-light dark:bg-secondary-dark
                                                               shadow-sm overflow-hidden
                                                               transition-all duration-200 ease-out
                                                               hover:-translate-y-0.5 hover:shadow-md"
                        style="--card-accent: {{ $cfg['accent'] }}; --card-glow: {{ $cfg['glow'] }};">
                        {{-- Top row: icon + badge --}}
                        <div class="flex items-start justify-between mb-4">
                            <div
                                class="flex items-center justify-center w-10 h-10 rounded-xl ring-1 {{ $cfg['icon_bg'] }} {{ $cfg['icon_border'] }} {{ $cfg['icon_color'] }} shrink-0">
                                <x-filament::icon :icon="$module['icon']" class="w-5 h-5" />
                            </div>
                        </div>

                        {{-- Label --}}
                        <p class="mb-1 text-base font-bold leading-tight text-black dark:text-white">
                            {{ $module['label'] }}
                        </p>

                        {{-- Description --}}
                        <p class="flex-1 mb-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $module['description'] }}
                        </p>

                        {{-- Footer --}}
                        {{-- Footer --}}
                        <div
                            class="flex items-center justify-between pt-3 border-t border-border-light dark:border-border-dark">

                            {{-- Teks Buka Modul / Memuat --}}
                            <span
                                class="text-sm font-medium text-gray-600 transition-colors duration-200 card-cta dark:text-gray-500">
                                <span wire:loading.remove wire:target="selectModule('{{ $module['key'] }}')">
                                    Buka modul
                                </span>
                                <span wire:loading wire:target="selectModule('{{ $module['key'] }}')">
                                    Memuat...
                                </span>
                            </span>

                            {{-- Lingkaran Icon (Panah / Spinner) --}}
                            <span
                                class="flex items-center justify-center w-6 h-6 text-gray-400 transition-all duration-200 rounded-full card-arrow ring-1 ring-border-light dark:ring-border-dark bg-main-light dark:bg-main-dark dark:text-gray-600">

                                {{-- Ikon Panah (Tampil saat normal) --}}
                                <svg wire:loading.remove wire:target="selectModule('{{ $module['key'] }}')" class="w-3 h-3"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>

                                {{-- Ikon Spinner Loading (Tampil saat di-klik) --}}
                                <svg wire:loading wire:target="selectModule('{{ $module['key'] }}')"
                                    class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>

                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- FOOTER --}}
        @if($modules->isNotEmpty())
            <div class="flex items-center justify-center gap-2 pt-8 pb-2 mt-6">
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-500 bg-secondary-light rounded-full dark:bg-secondary-dark dark:text-gray-400 ring-1 ring-border-light dark:ring-border-dark">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    <strong class="text-gray-700 dark:text-gray-300">{{ $modules->count() }}</strong> modul aktif untuk role
                    kamu
                </span>
            </div>
        @endif

    </div>

    @push('scripts')
        <script>
            function updateDashboardDateTime() {
                const dateEl = document.getElementById('header-date');
                const timeEl = document.getElementById('header-time');
                if (!dateEl || !timeEl) return;
                const now = new Date();
                const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                dateEl.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
                const h = String(now.getHours()).padStart(2, '0');
                const m = String(now.getMinutes()).padStart(2, '0');
                const s = String(now.getSeconds()).padStart(2, '0');
                timeEl.textContent = `${h}:${m}:${s}`;
            }
            setInterval(updateDashboardDateTime, 1000);
            document.addEventListener("DOMContentLoaded", updateDashboardDateTime);
            document.addEventListener("livewire:navigated", updateDashboardDateTime);
        </script>
    @endpush

</x-filament-panels::page>