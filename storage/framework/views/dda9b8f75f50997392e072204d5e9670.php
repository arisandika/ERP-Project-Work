<?php if (isset($component)) { $__componentOriginal5af00cf3bc55691449814fcba406195f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5af00cf3bc55691449814fcba406195f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-breezy::components.grid-section','data' => ['md' => '2','title' => __('filament-breezy::default.profile.2fa.title'),'description' => __('filament-breezy::default.profile.2fa.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-breezy::grid-section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['md' => '2','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('filament-breezy::default.profile.2fa.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('filament-breezy::default.profile.2fa.description'))]); ?>

    <?php if (isset($component)) { $__componentOriginal9b945b32438afb742355861768089b04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9b945b32438afb742355861768089b04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->showRequiresTwoFactorAlert()): ?>

            <div style="<?php echo e(\Illuminate\Support\Arr::toCssStyles([\Filament\Support\get_color_css_variables('danger',shades: [300, 400, 500, 600])])); ?>" class="p-4 rounded bg-custom-500">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <?php echo e(svg('heroicon-s-shield-exclamation', 'w-5 h-5 text-danger-600')); ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-danger-500">
                            <?php echo e(__('filament-breezy::default.profile.2fa.must_enable')); ?>

                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($user->hasEnabledTwoFactor())): ?>
            <h3 class="flex items-center gap-2 text-lg font-medium">
                <?php echo e(svg('heroicon-o-exclamation-circle', 'w-6')); ?>
                <?php echo e(__('filament-breezy::default.profile.2fa.not_enabled.title')); ?>

            </h3>
            <p class="text-sm"><?php echo e(__('filament-breezy::default.profile.2fa.not_enabled.description')); ?></p>

            <div class="flex justify-between mt-3">
                <?php echo e($this->enableAction); ?>

            </div>

        <?php else: ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->hasConfirmedTwoFactor()): ?>
                <h3 class="flex items-center gap-2 text-lg font-medium">
                    <?php echo e(svg('heroicon-o-shield-check', 'w-6')); ?>
                    <?php echo e(__('filament-breezy::default.profile.2fa.enabled.title')); ?>

                </h3>
                <p class="text-sm"><?php echo e(__('filament-breezy::default.profile.2fa.enabled.description')); ?></p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showRecoveryCodes): ?>
                    <div class="px-4 space-y-3">
                        <p class="text-xs"><?php echo e(__('filament-breezy::default.profile.2fa.enabled.store_codes')); ?></p>
                        <div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->recoveryCodes->toArray(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="inline-flex items-center p-1 text-xs font-medium text-gray-800 dark:text-gray-400 bg-gray-100 rounded-full dark:bg-gray-900"><?php echo e($code); ?></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div class="inline-block text-xs">
                            <?php if (isset($component)) { $__componentOriginal69e462a60300296161fd895ece21f085 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69e462a60300296161fd895ece21f085 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-breezy::components.clipboard-link','data' => ['data' => $this->recoveryCodes->join(',')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-breezy::clipboard-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->recoveryCodes->join(','))]); ?>
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
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="flex justify-between mt-3">
                    <?php echo e($this->regenerateCodesAction); ?>

                    <?php echo e($this->disableAction()->color('danger')); ?>

                </div>
            <?php else: ?>
                <h3 class="flex items-center gap-2 text-lg font-medium">
                    <?php echo e(svg('heroicon-o-question-mark-circle', 'w-6')); ?>
                    <?php echo e(__('filament-breezy::default.profile.2fa.finish_enabling.title')); ?>

                </h3>
                <p class="text-sm"><?php echo e(__('filament-breezy::default.profile.2fa.finish_enabling.description')); ?></p>
                <div class="flex mt-3 space-x-4 divide-x">
                    <div>
                        <?php echo $this->getTwoFactorQrCode(); ?>

                        <p class="pt-2 text-sm"><?php echo e(__('filament-breezy::default.profile.2fa.setup_key')); ?> <?php echo e(decrypt($this->user->two_factor_secret)); ?></p>
                    </div>
                    <div class="px-4 space-y-3">
                        <p class="text-xs"><?php echo e(__('filament-breezy::default.profile.2fa.enabled.store_codes')); ?></p>
                        <div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->recoveryCodes->toArray(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="inline-flex items-center p-1 text-xs font-medium text-gray-800 dark:text-gray-400 bg-gray-100 rounded-full dark:bg-gray-900"><?php echo e($code); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div class="inline-block text-xs">
                            <?php if (isset($component)) { $__componentOriginal69e462a60300296161fd895ece21f085 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69e462a60300296161fd895ece21f085 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-breezy::components.clipboard-link','data' => ['data' => $this->recoveryCodes->join(',')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-breezy::clipboard-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($this->recoveryCodes->join(','))]); ?>
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
                    </div>
                </div>

                <div class="flex justify-between mt-3">
                    <?php echo e($this->confirmAction); ?>

                    <?php echo e($this->disableAction); ?>

                </div>

            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9b945b32438afb742355861768089b04)): ?>
<?php $attributes = $__attributesOriginal9b945b32438afb742355861768089b04; ?>
<?php unset($__attributesOriginal9b945b32438afb742355861768089b04); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9b945b32438afb742355861768089b04)): ?>
<?php $component = $__componentOriginal9b945b32438afb742355861768089b04; ?>
<?php unset($__componentOriginal9b945b32438afb742355861768089b04); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal028e05680f6c5b1e293abd7fbe5f9758 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-actions::components.modals','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-actions::modals'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758)): ?>
<?php $attributes = $__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758; ?>
<?php unset($__attributesOriginal028e05680f6c5b1e293abd7fbe5f9758); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal028e05680f6c5b1e293abd7fbe5f9758)): ?>
<?php $component = $__componentOriginal028e05680f6c5b1e293abd7fbe5f9758; ?>
<?php unset($__componentOriginal028e05680f6c5b1e293abd7fbe5f9758); ?>
<?php endif; ?>
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
<?php /**PATH C:\laragon\www\erp-app\resources\views\vendor\filament-breezy\livewire\two-factor-authentication.blade.php ENDPATH**/ ?>