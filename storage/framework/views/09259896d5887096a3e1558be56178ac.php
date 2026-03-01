<div
    <?php echo e($attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)); ?>

>
    <?php echo e($getChildComponentContainer()); ?>

</div>
<?php /**PATH D:\Nurul Fauziah\Project Work\Nexicon\ERP-Project-Work\vendor\filament\infolists\resources\views/components/grid.blade.php ENDPATH**/ ?>