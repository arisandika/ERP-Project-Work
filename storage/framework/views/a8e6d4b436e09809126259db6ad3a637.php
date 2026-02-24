<div
    id="<?php echo e($record->getKey()); ?>"
    wire:click="recordClicked('<?php echo e($record->getKey()); ?>', <?php echo e(@json_encode($record)); ?>)"
    class="record bg-white dark:bg-gray-700 rounded-lg px-4 py-2 cursor-grab font-medium text-gray-600 dark:text-gray-200"
    <?php if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3): ?>
        x-data
        x-init="
            $el.classList.add('animate-pulse-twice', 'bg-primary-100', 'dark:bg-primary-800')
            $el.classList.remove('bg-white', 'dark:bg-gray-700')
            setTimeout(() => {
                $el.classList.remove('bg-primary-100', 'dark:bg-primary-800')
                $el.classList.add('bg-white', 'dark:bg-gray-700')
            }, 3000)
        "
    <?php endif; ?>
>
    <?php echo e($record->{static::$recordTitleAttribute}); ?>

</div>
<?php /**PATH C:\laragon\www\erp-app-v4\vendor\mokhosh\filament-kanban\resources\views/kanban-record.blade.php ENDPATH**/ ?>