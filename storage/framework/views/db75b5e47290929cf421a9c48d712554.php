<div class="p-4 timeline-history">

    <style>
        .timeline-history .vertical-line {
            position: absolute;
            left: 0;
            top: 5px;
            bottom: 5px;
            width: 2px;
        }

        .timeline-history .timeline-item {
            position: relative;
            padding-left: 25px;
            padding-bottom: 1.25rem;
        }

        .timeline-history .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-history .timeline-dot {
            position: absolute;
            left: -5px;
            top: 5px;
        }
    </style>

    <?php
        $histories = $getRecord()->histories()
            ->with(['employee', 'status'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'history_page');
    ?>

    <div class="relative">
        <div class="vertical-line bg-border-light dark:bg-border-dark"></div>

        <div class="space-y-5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="timeline-item">
                    <div class="w-3 h-3 rounded-full timeline-dot bg-main-primary ring-2 ring-main-primary/50">
                    </div>

                    <div>
                        <div>
                            <span class="font-semibold"
                                style="color: <?php echo e($history->status->color ?? '#6B7280'); ?>"><?php echo e($history->status->name); ?></span>
                        </div>

                        <div class="flex items-center mt-1 text-xs text-gray-400 gap-x-1">
                            <span>Diperbarui oleh <?php echo e($history->employee->full_name ?? 'System'); ?></span>
                            <span class="mx-1 text-gray-300">•</span>
                            <span><?php echo e($history->created_at->format('d M H:i')); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="pl-6 text-sm text-gray-500">
                    Belum ada riwayat status.
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($histories->hasPages()): ?>
        <div class="mt-8 bg-secondary-light dark:bg-secondary-dark rounded-b-2xl custom-pagination">
            <?php echo e($histories->links()); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div><?php /**PATH C:\laragon\www\erp-app-main\resources\views/filament/pages/ticket/timeline-history.blade.php ENDPATH**/ ?>