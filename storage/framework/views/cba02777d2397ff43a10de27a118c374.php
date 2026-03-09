<?php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
?>

<div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasPages()): ?>
        <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-between">
            <span>
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->onFirstPage()): ?>
                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-500 border rounded-md cursor-default bg-main-light border-border-light dark:bg-secondary-dark dark:border-border-dark dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-main-dark dark:active:text-gray-300">
                        <?php echo __('Sebelumnya'); ?>

                    </span>
                <?php else: ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(method_exists($paginator,'getCursorName')): ?>
                        <button type="button" dusk="previousPage" wire:key="cursor-<?php echo e($paginator->getCursorName()); ?>-<?php echo e($paginator->previousCursor()->encode()); ?>" wire:click="setPage('<?php echo e($paginator->previousCursor()->encode()); ?>','<?php echo e($paginator->getCursorName()); ?>')" x-on:click="<?php echo e($scrollIntoViewJsSnippet); ?>" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out border rounded-md bg-main-light border-border-light hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-secondary-light active:text-gray-700 dark:bg-secondary-dark dark:border-border-dark dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-main-dark dark:active:text-gray-300">
                                <?php echo __('Sebelumnya'); ?>

                        </button>
                    <?php else: ?>
                        <button
                            type="button" wire:click="previousPage('<?php echo e($paginator->getPageName()); ?>')" x-on:click="<?php echo e($scrollIntoViewJsSnippet); ?>" wire:loading.attr="disabled" dusk="previousPage<?php echo e($paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName()); ?>" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out border rounded-md bg-main-light border-border-light hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-secondary-light active:text-gray-700 dark:bg-secondary-dark dark:border-border-dark dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-main-dark dark:active:text-gray-300">
                                <?php echo __('Sebelumnya'); ?>

                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </span>

            <span>
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasMorePages()): ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(method_exists($paginator,'getCursorName')): ?>
                        <button type="button" dusk="nextPage" wire:key="cursor-<?php echo e($paginator->getCursorName()); ?>-<?php echo e($paginator->nextCursor()->encode()); ?>" wire:click="setPage('<?php echo e($paginator->nextCursor()->encode()); ?>','<?php echo e($paginator->getCursorName()); ?>')" x-on:click="<?php echo e($scrollIntoViewJsSnippet); ?>" wire:loading.attr="disabled" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out border rounded-md bg-main-light border-border-light hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-secondary-light active:text-gray-700 dark:bg-secondary-dark dark:border-border-dark dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-main-dark dark:active:text-gray-300">
                                <?php echo __('Selanjutnya'); ?>

                        </button>
                    <?php else: ?>
                        <button type="button" wire:click="nextPage('<?php echo e($paginator->getPageName()); ?>')" x-on:click="<?php echo e($scrollIntoViewJsSnippet); ?>" wire:loading.attr="disabled" dusk="nextPage<?php echo e($paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName()); ?>" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out border rounded-md bg-main-light border-border-light hover:text-gray-500 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-secondary-light active:text-gray-700 dark:bg-secondary-dark dark:border-border-dark dark:text-gray-300 dark:focus:border-blue-700 dark:active:bg-main-dark dark:active:text-gray-300">
                                <?php echo __('Selanjutnya'); ?>

                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium leading-5 text-gray-500 border rounded-md cursor-default bg-main-light border-border-light dark:text-gray-600 dark:bg-secondary-dark dark:border-border-dark">
                        <?php echo __('Selanjutnya'); ?>

                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </span>
        </nav>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\erp-app-main\resources\views/vendor/livewire/simple-tailwind.blade.php ENDPATH**/ ?>