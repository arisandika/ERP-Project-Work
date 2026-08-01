<?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $getEntryWrapperView()] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['entry' => $entry]); ?>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php
            $documents = $getState();
            // Pastikan data berupa array/collection yang bisa diloop
            if ($documents instanceof \Illuminate\Support\Collection) {
                $documents = $documents->all();
            }
            $documents = \Illuminate\Support\Arr::wrap($documents);
        ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                // Karena ini Model/Array dari relasi, cara ambil datanya beda:
                // Kita support akses object ($document->field) atau array ($document['field'])
                $filePath = data_get($document, 'file_path');
                $docName = data_get($document, 'document_name', basename($filePath)); // Pakai nama inputan user
                $docType = data_get($document, 'document_type');
                
                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $url = \Illuminate\Support\Facades\Storage::disk('public')->url($filePath);

                // Icon Mapping
                $icon = match ($extension) {
                    'pdf' => 'heroicon-o-document-text',
                    'doc', 'docx' => 'heroicon-o-document-text',
                    'xls', 'xlsx', 'csv' => 'heroicon-o-table-cells',
                    'jpg', 'jpeg', 'png', 'webp' => 'heroicon-o-photo',
                    'zip', 'rar' => 'heroicon-o-archive-box',
                    default => 'heroicon-o-document',
                };

                // Icon Color Mapping
                $iconColor = match ($extension) {
                    'pdf' => 'text-red-500',
                    'doc', 'docx' => 'text-blue-500',
                    'xls', 'xlsx', 'csv' => 'text-green-500',
                    'jpg', 'jpeg', 'png' => 'text-purple-500',
                    default => 'text-gray-500',
                };

                // Label Mapping untuk Document Type (Optional)
                $typeLabel = match ($docType) {
                    'contract' => 'Kontrak',
                    'bast' => 'BAST',
                    'technical' => 'Teknis',
                    'invoice' => 'Invoice',
                    default => ucfirst($docType ?? $extension),
                };
            ?>

            <a href="<?php echo e($url); ?>" 
               target="_blank"
               class="relative flex items-center gap-3 p-3 transition-all duration-200 border shadow-sm group rounded-xl bg-secondary-light border-border-light hover:bg-main-light hover:border-main-primary dark:bg-secondary-dark dark:border-border-dark dark:hover:bg-main-dark dark:hover:border-main-primary">
                
                
                <div class="flex-shrink-0 p-2 transition-colors rounded-lg bg-secondary-light dark:bg-secondary-dark group-hover:bg-white dark:group-hover:bg-accent-dark">
                    <?php if (isset($component)) { $__componentOriginal606b6d7eddc2e418f11096356be15e19 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal606b6d7eddc2e418f11096356be15e19 = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Icon::resolve(['name' => $icon] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-6 h-6 '.e($iconColor).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal606b6d7eddc2e418f11096356be15e19)): ?>
<?php $attributes = $__attributesOriginal606b6d7eddc2e418f11096356be15e19; ?>
<?php unset($__attributesOriginal606b6d7eddc2e418f11096356be15e19); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal606b6d7eddc2e418f11096356be15e19)): ?>
<?php $component = $__componentOriginal606b6d7eddc2e418f11096356be15e19; ?>
<?php unset($__componentOriginal606b6d7eddc2e418f11096356be15e19); ?>
<?php endif; ?>
                </div>

                
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-black truncate transition-colors dark:text-white group-hover:text-main-primary">
                        <?php echo e($docName); ?>

                    </p>
                    <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                        <span class="uppercase font-medium bg-gray-100 dark:bg-accent-dark px-1.5 py-0.5 rounded text-[10px]">
                            <?php echo e($extension); ?>

                        </span>
                        <span>&bull;</span>
                        <span class="truncate"><?php echo e($typeLabel); ?></span>
                    </div>
                </div>

                
                <div>
                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-m-arrow-down-tray'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4 text-gray-400 transition-transform group-hover:text-main-primary group-hover:-translate-y-0.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                </div>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($documents)): ?>
        <div class="flex flex-col items-center justify-center p-6 text-center border border-dashed rounded-xl border-border-light dark:border-border-dark">
            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-document'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-10 h-10 mb-2 text-gray-400']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Belum ada dokumen yang diunggah.
            </p>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?><?php /**PATH /var/www/erp-app-main/resources/views/filament/infolists/components/project-document-list.blade.php ENDPATH**/ ?>