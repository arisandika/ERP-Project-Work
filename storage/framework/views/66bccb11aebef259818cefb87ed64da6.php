

<div class="timeline-history">
    <style>
        .timeline-history .vertical-line {
            position: absolute;
            left: 0;
            top: 5px;
            bottom: 5px;
            width: 2px;
            background-color: #94a3b8;
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
            left: -9px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #34d399;
        }
    </style>

    <?php
        $histories = $getRecord()->histories()->with(['employee', 'status'])->orderBy('created_at', 'desc')->get();
    ?>
    
    <div class="relative">
        
        <div class="vertical-line"></div>
        
        
        <div class="space-y-5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $histories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="timeline-item">
                    
                    <div class="timeline-dot"></div>
                    
                    
                    <div>
                        <div>
                            <span class="text-base font-medium text-gray-900 dark:text-white"><?php echo e($history->status->name); ?></span>
                        </div>
                        
                        <div class="flex items-center mt-1 text-xs text-gray-400 gap-x-1">
                            <span>Updated by: <?php echo e($history->employee->full_name ?? 'System'); ?></span>
                            <span class="mx-1 text-gray-300">•</span>
                            <span><?php echo e($history->created_at->format('d M H:i')); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div><?php /**PATH C:\laragon\www\erp-app\resources\views\filament\pages\ticket\timeline-history.blade.php ENDPATH**/ ?>