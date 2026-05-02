<div class="mb-8">
    <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 border-gray-800 dark:border-gray-200 pb-2">
        <?php echo e($title); ?>

    </h3>

    <div class="space-y-1">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex justify-between py-1.5 px-2 rounded">
                <span class="text-gray-700 dark:text-gray-300 pl-4">
                    <?php echo e($item['category']); ?>

                </span>

                <div class="w-56 flex justify-between font-medium <?php echo e($item['net'] >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-danger-600 dark:text-danger-400'); ?>">
                    <span class="text-gray-400">Rp</span>
                    <span>
                        <?php echo e($item['net'] < 0 ? '(' : ''); ?><?php echo e(number_format(abs($item['net']), 0, ',', '.')); ?><?php echo e($item['net'] < 0 ? ')' : ''); ?>

                    </span>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-gray-400 pl-4 py-1.5 italic">
                Tidak ada transaksi pada bagian ini.
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
        <span class="uppercase text-xs md:text-sm">
            Total <?php echo e($title); ?>

        </span>

        <div class="w-56 flex justify-between <?php echo e($total >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'); ?>">
            <span class="opacity-60">Rp</span>
            <span>
                <?php echo e($total < 0 ? '(' : ''); ?><?php echo e(number_format(abs($total), 0, ',', '.')); ?><?php echo e($total < 0 ? ')' : ''); ?>

            </span>
        </div>
    </div>
</div>
<?php /**PATH D:\Nurul Fauziah\Project Work\Nexicon\ERP-Project-Work\resources\views/filament/pages/finance/partials/cash-flow-section.blade.php ENDPATH**/ ?>