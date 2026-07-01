<div
    x-data="{}"
    x-on:click="$dispatch('open-modal', { id: 'database-notifications' })"
    <?php echo e($attributes->class(['inline-block'])); ?>

>
    <?php echo e($slot); ?>

</div>
<?php /**PATH /home/nitro/projects/erp-app-main/vendor/filament/notifications/resources/views/components/database/trigger.blade.php ENDPATH**/ ?>