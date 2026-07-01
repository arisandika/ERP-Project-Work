<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php
        $assignment = $this->visitAssignment;
        $deal = $this->deal;
        $records = $this->visit_records;
    ?>

    <div class="w-full">
        <main class="max-w-7xl">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                
                
                <div class="space-y-6 md:col-span-8">
                    
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($assignment->notes): ?>
                        <div class="p-6 fi-section rounded-2xl ring-1">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-secondary-light dark:bg-secondary-dark ring-1 ring-border-light dark:ring-border-dark">
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-document-text'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-700 dark:text-gray-400']); ?>
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
                                <h2 class="text-base font-semibold">Instruksi / Catatan Penugasan</h2>
                            </div>
                            <div class="p-4 text-sm text-gray-700 whitespace-pre-wrap border rounded-xl bg-main-light border-border-light dark:bg-accent-dark dark:border-border-dark dark:text-gray-300">
                                <?php echo e($assignment->notes); ?>

                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <div class="p-6 fi-section rounded-2xl ring-1">
                        <div class="mb-6">
                            <h2 class="mb-1 text-base font-semibold">Riwayat Pelaksanaan Kunjungan</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Daftar rekaman kunjungan yang telah dilakukan.</p>
                        </div>
                        
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
                                    Gunakan tombol "Rekam Kunjungan" di kanan atas untuk memulai check-in di lokasi klien.
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
                                                    <span class="text-xs font-medium">
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
                                                        <span class="px-2 py-0.5 text-sm font-medium  rounded-md <?php echo e($this->getResultColorClass($record->visit_result)); ?>">
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
                                                                <img 
                                                                    src="<?php echo e($photo->url()); ?>" 
                                                                    alt="Foto" 
                                                                    class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-110"
                                                                >
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

                                
                                <div id="image-preview-modal" x-show="lightboxOpen" style="display: none;" class="fixed inset-0 z-[2000] flex items-center justify-center bg-black/70 backdrop-blur-sm"
                                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    @keydown.escape.window="lightboxOpen = false">
                                    <div class="relative w-full max-w-3xl mx-4" @click.away="lightboxOpen = false">
                                        <button @click="lightboxOpen = false" class="absolute z-50 flex items-center justify-center w-8 h-8 text-gray-800 bg-white rounded-full shadow -top-3 -right-3 hover:bg-gray-200">
                                            ✕
                                        </button>
                                        <img :src="lightboxImg" class="w-full max-h-[80vh] object-contain rounded-lg shadow-xl bg-black" />
                                        <p x-text="lightboxCaption" class="mt-4 text-sm text-center text-gray-300"></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                
                <div class="space-y-6 md:col-span-4">
                    
                    
                    <div class="p-6 fi-section rounded-2xl ring-1">
                        <div class="flex items-center justify-between pb-4">
                            <span class="text-base font-semibold">Status</span>
                            <span class="px-2.5 py-1 text-xs font-medium rounded-md <?php echo e($this->status_color); ?>">
                                <?php echo e(\App\Models\SalesActivity\VisitAssignment::statusOptions()[$assignment->status] ?? $assignment->status); ?>

                            </span>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div class="p-4 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                <span class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Tujuan Kunjungan</span>
                                <span class="inline-flex items-center gap-1.5 font-medium text-sm text-gray-900 dark:text-white">
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-flag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4 text-blue-500']); ?>
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
                                    <?php echo e(\App\Models\SalesActivity\VisitAssignment::purposeOptions()[$assignment->purpose] ?? $assignment->purpose); ?>

                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="p-4 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <span class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Tgl Rencana</span>
                                    <span class="block font-medium text-gray-900 dark:text-white">
                                        <?php echo e($assignment->visit_date ? \Carbon\Carbon::parse($assignment->visit_date)->format('d M') : '-'); ?>

                                    </span>
                                </div>
                                <div class="p-4 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <span class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Deadline</span>
                                    <span class="block font-medium <?php echo e($assignment->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'); ?>">
                                        <?php echo e($assignment->deadline_date ? \Carbon\Carbon::parse($assignment->deadline_date)->format('d M') : '-'); ?>

                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="p-6 fi-section rounded-2xl ring-1">
                        <div class="flex items-center gap-3 mb-4">
                            <h2 class="text-base font-semibold">Informasi Klien</h2>
                        </div>
                        
                        <div class="p-4 space-y-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                            <h4 class="text-base font-bold text-gray-900 dark:text-white"><?php echo e($this->client_name); ?></h4>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->client_phone): ?>
                                <div class="flex items-start gap-2 text-sm">
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-phone'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4 shrink-0 mt-0.5 text-gray-500']); ?>
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
                                    <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $this->client_phone)); ?>" target="_blank" class="text-green-600 dark:text-green-400 hover:underline">
                                        <?php echo e($this->client_phone); ?>

                                    </a>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->client_email): ?>
                                <div class="flex items-start gap-2 text-sm">
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-envelope'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4 shrink-0 mt-0.5 text-gray-500']); ?>
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
                                    <a href="mailto:<?php echo e($this->client_email); ?>" class="break-all text-warning-600 dark:text-warning-400 hover:underline">
                                        <?php echo e($this->client_email); ?>

                                    </a>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->client_address): ?>
                                <div class="flex items-start gap-2 text-sm">
                                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-map-pin'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4 shrink-0 mt-0.5 text-gray-500']); ?>
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
                                    <span class="text-gray-700 dark:text-gray-300"><?php echo e($this->client_address); ?></span>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($deal): ?>
                        <div class="p-6 fi-section rounded-2xl ring-1">
                            <div class="flex items-center gap-3 mb-4">
                                <h2 class="text-base font-semibold">Informasi Deal</h2>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3 text-sm">
                                <div class="p-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <dt class="mb-1 text-xs text-gray-500 dark:text-gray-400">No. Deal</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white"><?php echo e($deal->deal_number); ?></dd>
                                </div>
                                <div class="p-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <dt class="mb-1 text-xs text-gray-500 dark:text-gray-400">Judul Penawaran</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white"><?php echo e($deal->title); ?></dd>
                                </div>
                                <div class="p-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <dt class="mb-2 text-xs text-gray-500 dark:text-gray-400">Stage Saat Ini</dt>
                                    <dd>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            <?php echo e($deal->stage?->name ?? 'Unknown'); ?>

                                        </span>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                </div>
            </div>
        </main>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?><?php /**PATH /home/nitro/projects/erp-app-main/resources/views/filament/pages/sales-activity/my-visit-task-detail.blade.php ENDPATH**/ ?>