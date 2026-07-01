<div
    <?php echo e($attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)); ?>

>
    <?php echo e($getChildComponentContainer()); ?>

</div>
<?php /**PATH /home/nitro/projects/erp-app-main/vendor/filament/forms/resources/views/components/group.blade.php ENDPATH**/ ?>