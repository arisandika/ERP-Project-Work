
<div class="border border-gray-300 divide-y divide-gray-300 rounded-lg dark:border-white/10 dark:divide-white/10">
    
    <div class="flex text-sm font-medium text-black dark:text-white bg-main-light dark:bg-white/5">
        <div class="w-1/2 p-2">Nama Item</div>
        <div class="w-1/4 p-2 text-right">Jumlah</div>
        <div class="w-1/4 p-2 text-right">Harga Satuan</div>
        <div class="w-1/4 p-2 text-right">Subtotal</div>
    </div>

    
    <div class="divide-y divide-gray-300 dark:divide-white/10">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex text-sm text-gray-700 dark:text-gray-200">
                <div class="w-1/2 p-2">
                    <p class="font-medium"><?php echo e($item->item_name); ?></p>
                    <p class="text-xs text-gray-500"><?php echo e($item->item_code); ?></p>
                </div>
                <div class="w-1/4 p-2 text-right"><?php echo e(number_format($item->qty, 0)); ?></div>
                <div class="w-1/4 p-2 text-right"><?php echo e(number_format($item->unit_price, 2, ',', '.')); ?></div>
                <div class="w-1/4 p-2 text-right font-semibold"><?php echo e(number_format($item->line_total, 2, ',', '.')); ?></div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="p-4 text-center text-gray-500">
                Tidak ada item.
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH /var/www/erp-app-main/resources/views/filament/infolists/components/quotation-items-list.blade.php ENDPATH**/ ?>