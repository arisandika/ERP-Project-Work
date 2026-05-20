<div class="mt-6 mb-2 col-span-full">
    <div
        class="relative flex items-center gap-4 px-6 py-4 border border-gray-200 shadow-sm rounded-2xl bg-gradient-to-r from-gray-50 to-white dark:border-gray-700 dark:from-gray-800 dark:to-gray-900">
        
        <div class="absolute top-0 left-0 w-1 h-full rounded-l-2xl bg-gradient-to-b from-primary-500 to-primary-700">
        </div>

        
        <div
            class="flex items-center justify-center flex-shrink-0 h-11 w-11 rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">
            <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => $icon,'class' => 'w-6 h-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => 'w-6 h-6']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
        </div>

        
        <div class="flex-1">
            <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">
                <?php echo e($title); ?>

            </h2>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($description): ?>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    <?php echo e($description); ?>

                </p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="items-center hidden gap-1 sm:flex">
            <div class="w-2 h-2 rounded-full bg-primary-300 opacity-60"></div>
            <div class="w-2 h-2 rounded-full bg-primary-400 opacity-80"></div>
            <div class="w-3 h-3 rounded-full bg-primary-500"></div>
        </div>
    </div>
</div><?php /**PATH C:\laragon\www\erp-app-main\resources\views/filament/widgets/employee-analytics/sections/analytics-section-header-widget.blade.php ENDPATH**/ ?>