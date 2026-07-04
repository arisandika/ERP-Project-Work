<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

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

    <?php
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
    ?>

    <div class="flex flex-col items-center min-h-screen px-4 pb-8 sm:px-6 lg:px-8">

        
        <div class="flex items-center justify-between w-full max-w-6xl pb-6 mb-4">
            
            <div class="flex items-center gap-3">
                <img src="<?php echo e(asset('assets/logo-dark.webp')); ?>" alt="Logo" class="h-8 dark:hidden">
                <img src="<?php echo e(asset('assets/logo-light.webp')); ?>" alt="Logo" class="hidden h-8 dark:block">
            </div>

            
            <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400" id="header-date">Memuat...</p>
                <p class="text-base font-bold tracking-tight text-gray-800 tabular-nums dark:text-gray-100"
                    id="header-time">--:--:--</p>
            </div>
        </div>

        
        <div class="w-full max-w-4xl mb-6 text-center">
            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">
                <?php echo e($greeting); ?>, <span
                    class="font-semibold text-gray-700 dark:text-gray-300"><?php echo e($user?->name ?? 'User'); ?></span> 👋
            </p>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                Pilih modul kerja
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Akses semua area sistem dari satu tempat. Klik modul yang ingin kamu buka.
            </p>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isSuperAdmin && $user?->employee): ?>
            <div class="flex flex-wrap justify-center w-full gap-2 mb-6 max-w-7xl">

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pill['role']): ?>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:ring-slate-700">
                        <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-identification','class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-identification','class' => 'w-3.5 h-3.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?> <?php echo e($pill['role']); ?>

                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pill['department']): ?>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-violet-50 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-800">
                        <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-building-office','class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-building-office','class' => 'w-3.5 h-3.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?> <?php echo e($pill['department']); ?>

                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pill['is_weekend']): ?>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800">
                        <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-sun','class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-sun','class' => 'w-3.5 h-3.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?> Sedang akhir pekan
                    </span>
                <?php else: ?>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-blue-50 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-800">
                        <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-briefcase','class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-briefcase','class' => 'w-3.5 h-3.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?> Hari kerja aktif
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pill['shift']): ?>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-800">
                        <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-clock','class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-clock','class' => 'w-3.5 h-3.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                        <strong class="ml-1"><?php echo e($pill['shift']); ?></strong>
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pill['upcoming_leave']): ?>
                    <?php
                        $cStart = \Carbon\Carbon::parse($pill['upcoming_leave']['start']);
                        $cEnd = \Carbon\Carbon::parse($pill['upcoming_leave']['end']);
                        $isNow = $cStart->isToday() || ($cStart->isPast() && $cEnd->isFuture()) || $cEnd->isToday();
                    ?>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full ring-1
                                                    <?php echo e($isNow ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-800'); ?>">
                        <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-paper-airplane','class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-paper-airplane','class' => 'w-3.5 h-3.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isNow): ?>
                            Sedang cuti s/d <?php echo e($cEnd->translatedFormat('d M')); ?>

                        <?php else: ?>
                            Cuti: <?php echo e($cStart->translatedFormat('d M')); ?> - <?php echo e($cEnd->translatedFormat('d M')); ?>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pill['upcoming_holiday']): ?>
                    <?php
                        $hDate = \Carbon\Carbon::parse($pill['upcoming_holiday']['date']);
                        $isToday = $hDate->isToday();
                        $selisih = now()->diffInDays($hDate, false);
                    ?>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full ring-1
                                                    <?php echo e($isToday ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700'); ?>">
                        <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-star','class' => 'w-3.5 h-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-star','class' => 'w-3.5 h-3.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isToday): ?>
                            Libur: <?php echo e($pill['upcoming_holiday']['name']); ?>

                        <?php elseif($selisih <= 7): ?>
                            <?php echo e($pill['upcoming_holiday']['name']); ?> — <?php echo e($selisih); ?> hari lagi
                        <?php else: ?>
                            Libur: <?php echo e($hDate->translatedFormat('d M')); ?> (<?php echo e($pill['upcoming_holiday']['name']); ?>)
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isSuperAdmin && $infoMessage): ?>
            <?php
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
            ?>
            <div
                class="flex hover:underline-offset-4 items-center w-full max-w-2xl gap-4 p-4 mb-10 shadow-sm rounded-xl ring-1 <?php echo e($bgBanner); ?> <?php echo e($ringBanner); ?> <?php echo e($textBanner); ?>">
                <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => $icon,'class' => 'w-5 h-5 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => 'w-5 h-5 shrink-0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                <p class="flex-1 text-sm leading-relaxed"><?php echo $infoMessage; ?></p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modules->isEmpty()): ?>
            <div
                class="flex items-start w-full max-w-6xl gap-4 p-5 rounded-xl ring-1 ring-amber-200 bg-amber-50 dark:ring-amber-800 dark:bg-amber-950/30">
                <div
                    class="flex items-center justify-center rounded-lg w-9 h-9 bg-amber-100 dark:bg-amber-900/40 ring-1 ring-amber-200 dark:ring-amber-800 text-amber-600 dark:text-amber-400 shrink-0">
                    <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-exclamation-triangle','class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-exclamation-triangle','class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Belum ada modul yang bisa diakses
                    </p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-700 dark:text-amber-400">
                        Akun kamu belum memiliki permission modul apapun. Hubungi administrator untuk mengatur hak akses.
                    </p>
                </div>
            </div>

        <?php else: ?>
            <div class="grid w-full max-w-6xl grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $cfg = $moduleConfig[$module['key']] ?? $moduleConfig['system']; ?>

                    <button type="button" wire:click="selectModule('<?php echo e($module['key']); ?>')" class="module-card group relative flex flex-col p-4 sm:p-5 text-left rounded-2xl
                                                               ring-1 ring-border-light dark:ring-border-dark
                                                               bg-secondary-light dark:bg-secondary-dark
                                                               shadow-sm overflow-hidden
                                                               transition-all duration-200 ease-out
                                                               hover:-translate-y-0.5 hover:shadow-md"
                        style="--card-accent: <?php echo e($cfg['accent']); ?>; --card-glow: <?php echo e($cfg['glow']); ?>;">
                        
                        <div class="flex items-start justify-between mb-4">
                            <div
                                class="flex items-center justify-center w-10 h-10 rounded-xl ring-1 <?php echo e($cfg['icon_bg']); ?> <?php echo e($cfg['icon_border']); ?> <?php echo e($cfg['icon_color']); ?> shrink-0">
                                <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => $module['icon'],'class' => 'w-5 h-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module['icon']),'class' => 'w-5 h-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                            </div>
                        </div>

                        
                        <p class="mb-1 text-base font-bold leading-tight text-black dark:text-white">
                            <?php echo e($module['label']); ?>

                        </p>

                        
                        <p class="flex-1 mb-4 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e($module['description']); ?>

                        </p>

                        
                        
                        <div
                            class="flex items-center justify-between pt-3 border-t border-border-light dark:border-border-dark">

                            
                            <span
                                class="text-sm font-medium text-gray-600 transition-colors duration-200 card-cta dark:text-gray-500">
                                <span wire:loading.remove wire:target="selectModule('<?php echo e($module['key']); ?>')">
                                    Buka modul
                                </span>
                                <span wire:loading wire:target="selectModule('<?php echo e($module['key']); ?>')">
                                    Memuat...
                                </span>
                            </span>

                            
                            <span
                                class="flex items-center justify-center w-6 h-6 text-gray-400 transition-all duration-200 rounded-full card-arrow ring-1 ring-border-light dark:ring-border-dark bg-main-light dark:bg-main-dark dark:text-gray-600">

                                
                                <svg wire:loading.remove wire:target="selectModule('<?php echo e($module['key']); ?>')" class="w-3 h-3"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>

                                
                                <svg wire:loading wire:target="selectModule('<?php echo e($module['key']); ?>')"
                                    class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>

                            </span>
                        </div>
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modules->isNotEmpty()): ?>
            <div class="flex items-center justify-center gap-2 pt-8 pb-2 mt-6">
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-500 bg-secondary-light rounded-full dark:bg-secondary-dark dark:text-gray-400 ring-1 ring-border-light dark:ring-border-dark">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    <strong class="text-gray-700 dark:text-gray-300"><?php echo e($modules->count()); ?></strong> modul aktif untuk role
                    kamu
                </span>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    </div>

    <?php $__env->startPush('scripts'); ?>
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
    <?php $__env->stopPush(); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?><?php /**PATH /home/nitro/projects/erp-app-main/resources/views/filament/pages/module-selector.blade.php ENDPATH**/ ?>