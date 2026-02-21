<?php if (isset($component)) { $__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1 = $attributes; } ?>
<?php $component = Filament\View\LegacyComponents\Widget::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Filament\View\LegacyComponents\Widget::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

        <div class="flex items-center justify-between mb-2">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                Karyawan Cuti / Izin Hari Ini
            </h2>
            <span class="text-xs text-gray-500"><?php echo e(now()->format('d M Y')); ?></span>
        </div>

        <?php $leaves = $this->todayLeaves; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($leaves->isEmpty()): ?>
            <p class="text-sm italic text-gray-500">
                Tidak ada karyawan yang cuti atau izin hari ini.
            </p>
        <?php else: ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[300px] overflow-y-auto">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $leaves; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center gap-3 py-2">
                        <img src="<?php echo e($item->employee->photo ? Storage::url($item->employee->photo) : asset('assets/placeholder.jpg')); ?>"
                            alt="<?php echo e($item->employee->full_name); ?>"
                            class="object-cover w-10 h-10 border border-gray-300 rounded-full">

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo e($item->employee->full_name); ?>

                            </p>
                            <p class="text-xs text-gray-500"><?php echo e($item->employee->position ?? '-'); ?></p>
                            <p class="text-xs italic text-gray-400">
                                <?php echo e($item->leave->leave_type ?? 'Cuti/Izin'); ?>

                            </p>
                        </div>

                        <button wire:click="showEmployeeDetail(<?php echo e($item->employee->id); ?>)"
                            class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                            Lainnya
                        </button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


        <div x-show="$wire.isShowingEmployeeDetailModal" x-cloak x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div @click.away="$wire.closeEmployeeDetailModal()"
                class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 w-[500px] max-w-full">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->selectedEmployee): ?>
                    <?php $employee = (object) $this->selectedEmployee; ?>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-12 h-12 overflow-hidden rounded-full bg-slate-100">
                            <img src="<?php echo e($item->employee->photo ? Storage::url($item->employee->photo) : asset('assets/placeholder.jpg')); ?>"
                                alt="<?php echo e($item->employee->full_name); ?>" class="object-cover w-12 h-12 rounded-full">
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold leading-6"><?php echo e($employee->name); ?></h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($employee->position ?? '-'); ?></p>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-4 mt-6 text-sm">
                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Departemen</dt>
                            <dd class="font-medium"><?php echo e($employee->department ?? '-'); ?></dd>
                        </div>

                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Kantor</dt>
                            <dd class="font-medium"><?php echo e($employee->office ?? '-'); ?></dd>
                        </div>

                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Jadwal</dt>
                            <dd class="font-medium">
                                <?php echo e($employee->shift['name'] ?? '-'); ?>

                                (<?php echo e($employee->shift['start'] ? \Carbon\Carbon::parse($employee->shift['start'])->format('H:i') : '-'); ?>

                                -
                                <?php echo e($employee->shift['end'] ? \Carbon\Carbon::parse($employee->shift['end'])->format('H:i') : '-'); ?>)
                            </dd>
                        </div>

                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <dt class="text-gray-500 dark:text-gray-400">Tipe Karyawan</dt>
                            <dd class="font-medium">
                                <?php echo e($employee->can_wfa ? 'Bekerja dari rumah' : 'Bekerja dari kantor'); ?>

                                &
                                <?php echo e($employee->can_unlock_shift ? 'Jam kerja fleksibel' : 'Jam kerja tetap'); ?>

                            </dd>
                        </div>
                    </dl>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="mt-6 text-right">
                    <button wire:click="closeEmployeeDetailModal"
                        class="px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1)): ?>
<?php $attributes = $__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1; ?>
<?php unset($__attributesOriginald489e48d6214ecaf87e4b6a8ce684ad1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1)): ?>
<?php $component = $__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1; ?>
<?php unset($__componentOriginald489e48d6214ecaf87e4b6a8ce684ad1); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\erp-app\resources\views\filament\widgets\hr\attendance-leave-list-widget.blade.php ENDPATH**/ ?>