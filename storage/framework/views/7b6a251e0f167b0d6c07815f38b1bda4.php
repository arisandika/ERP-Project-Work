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
    .fi-sidebar { display: none !important; }

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

    /* ── Page ── */
    .erp-gateway {
        min-height: calc(100vh - 4.5rem);
        background:
            radial-gradient(ellipse at top left,  rgba(99,102,241,0.05) 0%, transparent 45%),
            radial-gradient(ellipse at top right, rgba(16,185,129,0.04) 0%, transparent 45%),
            rgb(var(--gray-100));
    }

    .dark .erp-gateway {
        background:
            radial-gradient(ellipse at top left,  rgba(99,102,241,0.07) 0%, transparent 45%),
            radial-gradient(ellipse at top right, rgba(16,185,129,0.05) 0%, transparent 45%),
            rgb(var(--gray-950));
    }

    .erp-wrapper {
        width: min(100% - 3rem, 1180px);
        margin-inline: auto;
        padding-block: 36px;
    }

    /* ── Topbar ── */
    .erp-topbar {
        margin-bottom: 32px;
    }

    .erp-system-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgb(var(--gray-50));
        border: 1px solid rgb(var(--gray-200));
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgb(var(--gray-400));
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .dark .erp-system-badge {
        background: rgb(var(--gray-800));
        border-color: rgb(var(--gray-700));
        color: rgb(var(--gray-500));
    }

    .erp-system-badge-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 2px rgba(34,197,94,0.2);
        flex-shrink: 0;
    }

    /* ── Hero ── */
    .erp-hero { margin-bottom: 24px; }

    .erp-hero-title {
        font-size: 28px;
        font-weight: 800;
        color: rgb(var(--gray-950));
        letter-spacing: -0.025em;
        margin: 0 0 8px;
        line-height: 1.2;
    }

    .dark .erp-hero-title {
        color: rgb(var(--gray-50));
    }

    .erp-hero-sub {
        font-size: 13.5px;
        color: rgb(var(--gray-400));
        margin: 0;
    }

    .erp-hero-sub strong {
        color: rgb(var(--gray-600));
        font-weight: 600;
    }

    .dark .erp-hero-sub strong {
        color: rgb(var(--gray-300));
    }

    /* ── Meta ── */
    .erp-meta { margin-bottom: 20px; }

    .erp-count-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgb(var(--gray-50));
        border: 1px solid rgb(var(--gray-200));
        border-radius: 999px;
        padding: 5px 13px;
        font-size: 12px;
        color: rgb(var(--gray-400));
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .dark .erp-count-pill {
        background: rgb(var(--gray-800));
        border-color: rgb(var(--gray-700));
        color: rgb(var(--gray-500));
    }

    .erp-count-pill strong {
        color: rgb(var(--gray-600));
        font-weight: 700;
    }

    .dark .erp-count-pill strong {
        color: rgb(var(--gray-300));
    }

    .erp-divider {
        height: 1px;
        background: rgb(var(--gray-200));
        margin-bottom: 20px;
    }

    .dark .erp-divider {
        background: rgb(var(--gray-800));
    }

    /* ── Grid ── */
    .erp-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    @media (min-width: 768px)  { .erp-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1100px) { .erp-grid { grid-template-columns: repeat(4, 1fr); } }

    /* ── Card ── */
    .erp-card {
        position: relative;
        background: rgb(var(--gray-50));
        border: 1px solid rgb(var(--gray-200));
        border-radius: 16px;
        padding: 18px;
        cursor: pointer;
        text-align: left;
        width: 100%;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15,23,42,0.04), 0 1px 2px rgba(15,23,42,0.03);
        transition: transform 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
    }

    .dark .erp-card {
        background: rgb(var(--gray-900));
        border-color: rgb(var(--gray-800));
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .erp-card::before {
        content: "";
        position: absolute;
        top: -24px;
        right: -24px;
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: var(--glow);
        filter: blur(32px);
        opacity: 0.15;
        pointer-events: none;
        transition: opacity 160ms ease, transform 160ms ease;
    }

    .dark .erp-card::before { opacity: 0.3; }

    .erp-card::after {
        content: "";
        position: absolute;
        left: 20px;
        right: 20px;
        bottom: 0;
        height: 3px;
        border-radius: 999px 999px 0 0;
        background: var(--accent);
        opacity: 0;
        transform: scaleX(0.5);
        transition: opacity 160ms ease, transform 160ms ease;
    }

    .erp-card:hover {
        transform: translateY(-3px);
        border-color: var(--accent);
        box-shadow:
            0 8px 24px rgba(15,23,42,0.08),
            0 0 0 1px var(--accent);
    }

    .dark .erp-card:hover {
        box-shadow:
            0 8px 24px rgba(0,0,0,0.3),
            0 0 0 1px var(--accent);
    }

    .erp-card:hover::before {
        opacity: 0.35;
        transform: scale(1.2);
    }

    .dark .erp-card:hover::before { opacity: 0.55; }

    .erp-card:hover::after {
        opacity: 1;
        transform: scaleX(1);
    }

    /* ── Card internals ── */
    .erp-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .erp-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--icon-bg);
        border: 1px solid var(--icon-border);
        color: var(--accent);
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }

    .erp-card-icon svg { width: 20px; height: 20px; }

    .erp-card-tag {
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--accent);
        background: var(--tag-bg);
        border: 1px solid var(--icon-border);
        border-radius: 999px;
        padding: 3px 9px;
        position: relative;
        z-index: 1;
    }

    .erp-card-label {
        font-size: 14px;
        font-weight: 800;
        color: rgb(var(--gray-900));
        margin: 0 0 5px;
        letter-spacing: -0.01em;
        position: relative;
        z-index: 1;
    }

    .dark .erp-card-label {
        color: rgb(var(--gray-100));
    }

    .erp-card-desc {
        font-size: 11.5px;
        color: rgb(var(--gray-400));
        line-height: 1.6;
        margin: 0 0 16px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        position: relative;
        z-index: 1;
    }

    .dark .erp-card-desc {
        color: rgb(var(--gray-500));
    }

    .erp-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid rgb(var(--gray-100));
        padding-top: 12px;
        position: relative;
        z-index: 1;
    }

    .dark .erp-card-footer {
        border-top-color: rgb(var(--gray-800));
    }

    .erp-cta-text {
        font-size: 11px;
        font-weight: 600;
        color: rgb(var(--gray-300));
        transition: color 160ms ease;
    }

    .dark .erp-cta-text {
        color: rgb(var(--gray-600));
    }

    .erp-card:hover .erp-cta-text { color: var(--accent); }

    .erp-arrow {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: rgb(var(--gray-100));
        border: 1px solid rgb(var(--gray-200));
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgb(var(--gray-300));
        transition: background 160ms ease, color 160ms ease, border-color 160ms ease, transform 160ms ease;
        flex-shrink: 0;
    }

    .dark .erp-arrow {
        background: rgb(var(--gray-800));
        border-color: rgb(var(--gray-700));
        color: rgb(var(--gray-600));
    }

    .erp-arrow svg { width: 13px; height: 13px; }

    .erp-card:hover .erp-arrow {
        background: var(--accent);
        color: #fff;
        border-color: var(--accent);
        transform: translateX(2px);
    }

    /* ── Empty state ── */
    .erp-empty {
        border: 1px solid rgb(var(--warning-200));
        background: rgb(var(--warning-50));
        border-radius: 16px;
        padding: 24px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

    .dark .erp-empty {
        border-color: rgb(var(--warning-800));
        background: rgb(var(--warning-950));
    }

    .erp-empty-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgb(var(--warning-100));
        border: 1px solid rgb(var(--warning-200));
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgb(var(--warning-600));
        flex-shrink: 0;
    }

    .dark .erp-empty-icon {
        background: rgb(var(--warning-900));
        border-color: rgb(var(--warning-800));
        color: rgb(var(--warning-400));
    }

    .erp-empty-icon svg { width: 20px; height: 20px; }

    .erp-empty-title {
        font-size: 13px;
        font-weight: 700;
        color: rgb(var(--warning-800));
        margin: 0 0 4px;
    }

    .dark .erp-empty-title {
        color: rgb(var(--warning-200));
    }

    .erp-empty-desc {
        font-size: 12px;
        color: rgb(var(--warning-700));
        margin: 0;
        line-height: 1.6;
    }

    .dark .erp-empty-desc {
        color: rgb(var(--warning-400));
    }

    @media (max-width: 640px) {
        .erp-wrapper { padding-block: 20px; }
        .erp-hero-title { font-size: 22px; }
        .erp-card { padding: 14px; }
    }
</style>

<?php
    $modules = $this->getModules();
    $user    = auth()->user();

    $moduleStyles = [
        'attendance'  => ['accent' => '#3b82f6', 'glow' => 'rgba(59,130,246,0.9)',   'icon_bg' => 'rgba(59,130,246,0.08)',  'icon_border' => 'rgba(59,130,246,0.2)',   'tag_bg' => 'rgba(59,130,246,0.07)'],
        'hr'          => ['accent' => '#6366f1', 'glow' => 'rgba(99,102,241,0.9)',   'icon_bg' => 'rgba(99,102,241,0.08)',  'icon_border' => 'rgba(99,102,241,0.2)',   'tag_bg' => 'rgba(99,102,241,0.07)'],
        'finance'     => ['accent' => '#10b981', 'glow' => 'rgba(16,185,129,0.9)',   'icon_bg' => 'rgba(16,185,129,0.08)',  'icon_border' => 'rgba(16,185,129,0.2)',   'tag_bg' => 'rgba(16,185,129,0.07)'],
        'sales'       => ['accent' => '#8b5cf6', 'glow' => 'rgba(139,92,246,0.9)',   'icon_bg' => 'rgba(139,92,246,0.08)',  'icon_border' => 'rgba(139,92,246,0.2)',   'tag_bg' => 'rgba(139,92,246,0.07)'],
        'procurement' => ['accent' => '#f59e0b', 'glow' => 'rgba(245,158,11,0.9)',   'icon_bg' => 'rgba(245,158,11,0.08)',  'icon_border' => 'rgba(245,158,11,0.2)',   'tag_bg' => 'rgba(245,158,11,0.07)'],
        'inventory'   => ['accent' => '#64748b', 'glow' => 'rgba(100,116,139,0.9)',  'icon_bg' => 'rgba(100,116,139,0.08)', 'icon_border' => 'rgba(100,116,139,0.2)',  'tag_bg' => 'rgba(100,116,139,0.07)'],
        'crm'         => ['accent' => '#06b6d4', 'glow' => 'rgba(6,182,212,0.9)',    'icon_bg' => 'rgba(6,182,212,0.08)',   'icon_border' => 'rgba(6,182,212,0.2)',    'tag_bg' => 'rgba(6,182,212,0.07)'],
        'marketing'   => ['accent' => '#ec4899', 'glow' => 'rgba(236,72,153,0.9)',   'icon_bg' => 'rgba(236,72,153,0.08)',  'icon_border' => 'rgba(236,72,153,0.2)',   'tag_bg' => 'rgba(236,72,153,0.07)'],
        'system'      => ['accent' => '#94a3b8', 'glow' => 'rgba(148,163,184,0.9)',  'icon_bg' => 'rgba(148,163,184,0.08)', 'icon_border' => 'rgba(148,163,184,0.2)',  'tag_bg' => 'rgba(148,163,184,0.07)'],
    ];
?>

<div class="erp-gateway">
    <div class="erp-wrapper">

        
        <div class="erp-topbar">
            <div class="erp-system-badge">
                <span class="erp-system-badge-dot"></span>
                INTERAERP &nbsp;·&nbsp; Module Gateway
            </div>
        </div>

        
        <div class="erp-hero">
            <h1 class="erp-hero-title">Pilih Modul</h1>
            <p class="erp-hero-sub">
                Selamat datang, <strong><?php echo e($user?->name ?? 'User'); ?></strong>.
                Pilih area kerja sesuai role kamu.
            </p>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modules->isNotEmpty()): ?>
            <div class="erp-meta">
                <div class="erp-count-pill">
                    <strong><?php echo e($modules->count()); ?></strong> modul tersedia
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="erp-divider"></div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modules->isEmpty()): ?>
            <div class="erp-empty">
                <div class="erp-empty-icon">
                    <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-exclamation-triangle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-exclamation-triangle']); ?>
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
                    <p class="erp-empty-title">Belum ada modul yang bisa diakses</p>
                    <p class="erp-empty-desc">
                        Akun kamu belum memiliki permission modul apapun.
                        Silakan hubungi administrator untuk mengatur role.
                    </p>
                </div>
            </div>

        
        <?php else: ?>
            <div class="erp-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $s = $moduleStyles[$module['key']] ?? $moduleStyles['system'];
                    ?>

                    <button
                        type="button"
                        wire:click="selectModule('<?php echo e($module['key']); ?>')"
                        class="erp-card"
                        style="
                            --accent: <?php echo e($s['accent']); ?>;
                            --glow: <?php echo e($s['glow']); ?>;
                            --icon-bg: <?php echo e($s['icon_bg']); ?>;
                            --icon-border: <?php echo e($s['icon_border']); ?>;
                            --tag-bg: <?php echo e($s['tag_bg']); ?>;
                        "
                    >
                        <div class="erp-card-top">
                            <div class="erp-card-icon">
                                <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => $module['icon']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module['icon'])]); ?>
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
                            <span class="erp-card-tag">Modul</span>
                        </div>

                        <p class="erp-card-label"><?php echo e($module['label']); ?></p>
                        <p class="erp-card-desc"><?php echo e($module['description']); ?></p>

                        <div class="erp-card-footer">
                            <span class="erp-cta-text">Buka modul</span>
                            <span class="erp-arrow">
                                <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-arrow-right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-arrow-right']); ?>
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
                            </span>
                        </div>
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    </div>
</div>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH /var/www/erp-app-main/resources/views/filament/pages/module-selector-v1.blade.php ENDPATH**/ ?>