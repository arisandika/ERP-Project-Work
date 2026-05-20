<?php
    $calendarData = $this->getCalendarData();

    // Group by month
    $byMonth = collect($calendarData)->groupBy('month');

    $statusConfig = [
        'hadir' => ['color' => 'bg-green-500', 'dark' => 'dark:bg-green-600', 'label' => 'Hadir', 'dot' => '#22c55e'],
        'terlambat' => ['color' => 'bg-amber-400', 'dark' => 'dark:bg-amber-500', 'label' => 'Terlambat', 'dot' => '#f59e0b'],
        'absen' => ['color' => 'bg-red-500', 'dark' => 'dark:bg-red-600', 'label' => 'Absen', 'dot' => '#ef4444'],
        'cuti' => ['color' => 'bg-blue-400', 'dark' => 'dark:bg-blue-500', 'label' => 'Cuti', 'dot' => '#3b82f6'],
        'izin' => ['color' => 'bg-purple-400', 'dark' => 'dark:bg-purple-500', 'label' => 'Izin', 'dot' => '#a855f7'],
        'no_checkout' => ['color' => 'bg-orange-400', 'dark' => 'dark:bg-orange-500', 'label' => 'Lupa Checkout', 'dot' => '#f97316'],
        'holiday' => ['color' => 'bg-rose-200', 'dark' => 'dark:bg-rose-900', 'label' => 'Libur Nasional', 'dot' => '#fda4af'],
        'weekend' => ['color' => 'bg-gray-100', 'dark' => 'dark:bg-gray-800', 'label' => 'Akhir Pekan', 'dot' => '#e5e7eb'],
        'libur' => ['color' => 'bg-rose-200', 'dark' => 'dark:bg-rose-900', 'label' => 'Libur', 'dot' => '#fda4af'],
        'belum_presensi' => ['color' => 'bg-gray-200', 'dark' => 'dark:bg-gray-700', 'label' => 'Belum Presensi', 'dot' => '#d1d5db'],
    ];
?>

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
         <?php $__env->slot('heading', null, []); ?> Kalender Kehadiran <?php $__env->endSlot(); ?>
         <?php $__env->slot('description', null, []); ?> Visualisasi kehadiran harian dalam periode yang dipilih <?php $__env->endSlot(); ?>

        
        <div class="flex flex-wrap gap-3 mb-5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = ['hadir', 'terlambat', 'absen', 'cuti', 'izin', 'no_checkout', 'holiday', 'weekend']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                    <span
                        class="h-3 w-3 rounded-sm <?php echo e($statusConfig[$s]['color']); ?> <?php echo e($statusConfig[$s]['dark']); ?>"></span>
                    <?php echo e($statusConfig[$s]['label']); ?>

                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="space-y-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $byMonth; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month => $days): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div>
                    <p class="mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                        <?php echo e($month); ?></p>

                    
                    <div class="grid grid-cols-7 mb-1">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="text-center text-[10px] font-medium text-gray-400"><?php echo e($d); ?></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    
                    <?php
                        $firstDay = $days->first();
                        $startOffset = ($firstDay['dayOfWeek'] - 1); // 0-based Mon
                    ?>

                    <div class="grid grid-cols-7 gap-1">
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 0; $i < $startOffset; $i++): ?>
                            <div></div>
                        <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $cfg = $statusConfig[$day['status']] ?? $statusConfig['belum_presensi'];
                                $opacity = $day['isFuture'] ? 'opacity-30' : '';
                            ?>
                            <div x-data x-tooltip.raw="<?php echo e($day['date']); ?>: <?php echo e($day['label']); ?>"
                                class="relative flex h-8 w-full items-center justify-center rounded-md text-[11px] font-medium cursor-default select-none
                                            <?php echo e($cfg['color']); ?> <?php echo e($cfg['dark']); ?> <?php echo e($opacity); ?>

                                            <?php echo e(in_array($day['status'], ['hadir', 'terlambat', 'absen', 'cuti', 'izin', 'no_checkout']) ? 'text-white' : 'text-gray-500 dark:text-gray-400'); ?>">
                                <?php echo e($day['day']); ?>

                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
<?php endif; ?><?php /**PATH C:\laragon\www\erp-app-main\resources\views/filament/widgets/employee-analytics/attendance/attendance-heatmap-chart.blade.php ENDPATH**/ ?>