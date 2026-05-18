<div style="width: 100%;">
    <?php
        $assignment = $getRecord();
        $records = $assignment->visitRecords()->with('photos')->orderBy('visit_order', 'asc')->get();
    ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($records->isEmpty()): ?>
        <div class="flex flex-col items-center justify-center py-10 text-center border border-dashed rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-map-pin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-12 h-12 mb-3 text-gray-400 dark:text-gray-500']); ?>
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
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Kunjungan</h3>
            <p class="mt-1 text-xs text-gray-500 max-w-[300px] dark:text-gray-400">
                Gunakan menu Rekam Kunjungan untuk memulai check-in di lokasi klien.
            </p>
        </div>
    <?php else: ?>
        <div x-data="{ 
                lightboxOpen: false, 
                lightboxImg: '', 
                lightboxCaption: '',
                openGallery(url, caption) {
                    this.lightboxImg = url;
                    this.lightboxCaption = caption;
                    this.lightboxOpen = true;
                }
            }" 
            class="relative"
        >
            
            <div class="absolute left-0 w-px h-full mt-2 bg-border-light dark:bg-border-dark" style="left: 0.35rem;"></div>

            <div class="pl-8 space-y-8">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="relative">
                        
                        <div class="absolute -left-[32px] w-3 h-3 mt-1.5 bg-blue-600 rounded-full ring-4 ring-white dark:ring-gray-900 dark:bg-blue-500"></div>

                        
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-gray-900 text-md dark:text-white">
                                    Kunjungan ke-<?php echo e($record->visit_order); ?>

                                </h3>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <?php echo e($record->visited_at ? $record->visited_at->format('d M Y, H:i') : '-'); ?>

                                </span>
                            </div>

                            <div class="flex flex-col gap-3 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->location_address): ?>
                                    <a href="<?php echo e($record->googleMapsUrl()); ?>" target="_blank" class="flex items-start gap-1 transition-colors hover:text-orange-600 dark:hover:text-orange-400">
                                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-map-pin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4 mt-0.5']); ?>
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
                                        <span class=""><?php echo e($record->location_address); ?></span>
                                    </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    Hasil Kunjungan: 
                                    <span class="px-2 py-0.5 text-sm font-medium rounded-md border 
                                        <?php echo e(match($record->visit_result) {
                                            'interested', 'deal_progressed' => 'text-emerald-600 bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800',
                                            'not_interested', 'failed' => 'text-red-600 bg-red-50 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
                                            'need_followup' => 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800',
                                            default => 'text-gray-600 bg-gray-50 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'
                                        }); ?>">
                                        <?php echo e(\App\Models\SalesActivity\VisitRecord::resultOptions()[$record->visit_result] ?? $record->visit_result); ?>

                                    </span>
                                </div>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->description): ?>
                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                    Deskripsi: <?php echo e($record->description); ?>

                                </p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->next_followup_date || $record->followup_notes): ?>
                                <div class="p-3 mt-3 text-xs border rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 border-amber-200 dark:border-amber-800/50">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->next_followup_date): ?>
                                        <div class="flex items-center gap-1 mb-1 font-semibold">
                                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-calendar-days'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4']); ?>
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
                                            Follow-up berikutnya: <?php echo e(\Carbon\Carbon::parse($record->next_followup_date)->format('d M Y')); ?>

                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->followup_notes): ?>
                                        <p class="opacity-90"><?php echo e($record->followup_notes); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <div class="mt-4">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->photos && $record->photos->count() > 0): ?>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $record->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div 
                                            @click="openGallery('<?php echo e($photo->url()); ?>', '<?php echo e(addslashes($photo->caption ?? 'Foto #' . $loop->iteration)); ?>')"
                                            class="relative overflow-hidden transition-all border cursor-pointer rounded-xl group border-border-light dark:border-border-dark aspect-square hover:ring-2 hover:ring-blue-500"
                                        >
                                            <img src="<?php echo e($photo->url()); ?>" alt="Foto" class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-110">
                                            <div class="absolute inset-0 transition-opacity bg-black opacity-0 group-hover:opacity-10"></div>
                                            <div class="absolute top-1 left-1">
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm bg-white/90 text-gray-800 uppercase tracking-wider">
                                                    <?php echo e(\App\Models\SalesActivity\VisitPhoto::typeOptions()[$photo->photo_type] ?? $photo->photo_type); ?>

                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="px-3 py-2 text-xs italic text-gray-500 border rounded-xl border-border-light bg-main-light dark:bg-accent-dark dark:text-gray-400 dark:border-border-dark w-fit">
                                    Tidak ada foto dilampirkan.
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div x-show="lightboxOpen" style="display: none;" class="fixed inset-0 z-[2000] flex items-center justify-center bg-black/70 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                @keydown.escape.window="lightboxOpen = false">
                <div class="relative w-full max-w-3xl mx-4" @click.away="lightboxOpen = false">
                    <button @click="lightboxOpen = false" class="absolute z-50 flex items-center justify-center w-8 h-8 text-gray-800 bg-white rounded-full shadow -top-3 -right-3 hover:bg-gray-200">
                        ✕
                    </button>
                    <img :src="lightboxImg" class="w-full max-h-[80vh] object-contain rounded-lg shadow-xl bg-black" />
                    <p x-text="lightboxCaption" class="mt-4 text-sm text-center text-white"></p>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\laragon\www\erp-app-main\resources\views/filament/infolists/components/visit-timeline.blade.php ENDPATH**/ ?>