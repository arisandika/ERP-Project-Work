<div
    <?php echo e($attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)); ?>

>
    <?php echo e($getChildComponentContainer()); ?>

</div>
<?php /**PATH D:\Nurul Fauziah\Project Work\Nexicon\ERP-Project-Work\vendor\filament\forms\resources\views/components/group.blade.php ENDPATH**/ ?>