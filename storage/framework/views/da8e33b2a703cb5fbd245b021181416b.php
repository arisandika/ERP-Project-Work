<?php if (isset($component)) { $__componentOriginal5af00cf3bc55691449814fcba406195f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5af00cf3bc55691449814fcba406195f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-breezy::components.grid-section','data' => ['md' => '2','title' => __('filament-breezy::default.profile.sanctum.title'),'description' => __('filament-breezy::default.profile.sanctum.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-breezy::grid-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['md' => '2','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('filament-breezy::default.profile.sanctum.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('filament-breezy::default.profile.sanctum.description'))]); ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($plainTextToken): ?>
            <div class="space-y-2 bg-warning-500 p-4 rounded">
                <p class="text-sm"><?php echo e(__('filament-breezy::default.profile.sanctum.create.message')); ?></p>
                <input type="text" disabled class="<?php echo \Illuminate\Support\Arr::toCssClasses(['w-full py-1 px-3 rounded-lg bg-gray-100 border-gray-200 dark:bg-gray-700 dark:border-gray-500']); ?>" name="plain_text_token" value="<?php echo e($plainTextToken); ?>" />
                <div class="flex items-center justify-between">
                    <div class="inline-block text-xs">
                        <?php if (isset($component)) { $__componentOriginal69e462a60300296161fd895ece21f085 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69e462a60300296161fd895ece21f085 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-breezy::components.clipboard-link','data' => ['data' => $plainTextToken]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-breezy::clipboard-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($plainTextToken)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal69e462a60300296161fd895ece21f085)): ?>
<?php $attributes = $__attributesOriginal69e462a60300296161fd895ece21f085; ?>
<?php unset($__attributesOriginal69e462a60300296161fd895ece21f085); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal69e462a60300296161fd895ece21f085)): ?>
<?php $component = $__componentOriginal69e462a60300296161fd895ece21f085; ?>
<?php unset($__componentOriginal69e462a60300296161fd895ece21f085); ?>
<?php endif; ?>
                    </div>
                    <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['icon' => 'heroicon-s-clipboard-document-check','size' => 'sm','type' => 'button','wire:click' => '$set(\'plainTextToken\',null)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-s-clipboard-document-check','size' => 'sm','type' => 'button','wire:click' => '$set(\'plainTextToken\',null)']); ?><?php echo e(__('filament-breezy::default.profile.sanctum.copied.label')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $attributes = $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $component = $__componentOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
                </div>

            </div>

        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div style="display: <?php echo e($plainTextToken ? 'none' : ''); ?>">
            <?php echo e($this->table); ?>

        </div>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5af00cf3bc55691449814fcba406195f)): ?>
<?php $attributes = $__attributesOriginal5af00cf3bc55691449814fcba406195f; ?>
<?php unset($__attributesOriginal5af00cf3bc55691449814fcba406195f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5af00cf3bc55691449814fcba406195f)): ?>
<?php $component = $__componentOriginal5af00cf3bc55691449814fcba406195f; ?>
<?php unset($__componentOriginal5af00cf3bc55691449814fcba406195f); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\erp-app\resources\views\vendor\filament-breezy\livewire\sanctum-tokens.blade.php ENDPATH**/ ?>