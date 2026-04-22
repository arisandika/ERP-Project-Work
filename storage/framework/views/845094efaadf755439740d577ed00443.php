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

    <div
        class="flex flex-wrap items-center gap-5 p-4 mb-6 text-sm text-black border rounded-2xl border-border-light dark:border-border-dark bg-secondary-light dark:bg-secondary-dark dark:text-gray-400">
        <div class="flex items-center gap-2">
            <span
                class="w-4 h-4 bg-red-100 border border-red-300 rounded-full dark:bg-red-900/40 dark:border-red-800"></span>
            <span class="text-sm text-black dark:text-white">Hari Libur <span
                    class="text-xs text-gray-500 dark:text-gray-400">(Klik detail)</span></span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded-full shadow-sm bg-main-primary ring-2 ring-main-primary/30"></span>
            <span class="text-sm text-black dark:text-white">Hari Ini</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 bg-red-500 rounded-full shadow-sm ring-2 ring-red-500/30"></span>
            <span class="text-sm text-black dark:text-white">Hari Ini (Libur)</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="font-bold text-red-500">Min</span>
            <span class="text-sm text-black dark:text-white">Hari Minggu</span>
        </div>
        <div class="ml-auto font-medium text-gray-500 dark:text-gray-400">
            Total Libur: <strong class="text-black dark:text-white"><?php echo e(count($holidays)); ?> hari</strong>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php
            $today = \Carbon\Carbon::today()->format('Y-m-d');
            $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $monthPad = str_pad($month['month'], 2, '0', STR_PAD_LEFT);

                $rows = [];
                $row = array_fill(0, $month['startDow'], null);

                for ($d = 1; $d <= $month['daysInMonth']; $d++) {
                    $dateStr = $month['year'] . '-' . $monthPad . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                    $isHoliday = isset($holidays[$dateStr]);
                    $isToday = $dateStr === $today;
                    $dow = (count($row)) % 7;
                    $isSunday = $dow === 6;

                    $baseClass = "inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-medium transition-all duration-200 ";

                    // LOGIC BARU: Cek jika Hari ini DAN Hari Libur
                    if ($isToday && $isHoliday) {
                        $cls = $baseClass . "bg-red-400 text-white font-bold shadow-md ring-4 ring-red-500/30 dark:ring-red-500/40 cursor-pointer transform hover:scale-110 hover:bg-red-600 dark:hover:bg-red-600";
                    }
                    // Jika hanya Hari ini
                    elseif ($isToday) {
                        $cls = $baseClass . "bg-main-primary text-white font-bold shadow-md ring-2 ring-main-primary/30";
                    }
                    // Jika hanya Hari Libur biasa
                    elseif ($isHoliday) {
                        $cls = $baseClass . "bg-red-100 text-red-600 border border-red-300 dark:bg-red-900/40 dark:text-red-400 dark:border-red-800 hover:bg-red-200 dark:hover:bg-red-900/60 cursor-pointer transform hover:scale-110";
                    }
                    // Jika hanya Hari Minggu (Background tipis & text bold)
                    elseif ($isSunday) {
                        $cls = $baseClass . "text-red-500 font-bold";
                    }
                    // Hari Biasa
                    else {
                        $cls = $baseClass . "text-black dark:text-white hover:bg-secondary-light dark:hover:bg-secondary-dark";
                    }

                    $row[] = [
                        'day' => $d,
                        'class' => $cls,
                        'tooltip' => $isHoliday ? $holidays[$dateStr]['name'] : '',
                        'isHoliday' => $isHoliday,
                        'holiday_id' => $isHoliday ? $holidays[$dateStr]['id'] : null,
                    ];

                    if (count($row) === 7) {
                        $rows[] = $row;
                        $row = [];
                    }
                }

                if (count($row) > 0) {
                    while (count($row) < 7) {
                        $row[] = null;
                    }
                    $rows[] = $row;
                }
            ?>

            <div
                class="overflow-hidden transition-shadow border rounded-2xl border-border-light dark:border-border-dark bg-main-light dark:bg-main-dark hover:shadow-sm">
                <div
                    class="px-4 py-3 text-sm font-semibold tracking-wider text-center text-black uppercase border-b bg-secondary-light dark:bg-secondary-dark border-border-light dark:border-border-dark dark:text-white">
                    <?php echo e($month['name']); ?>

                </div>

                <div class="p-2 bg-main-light dark:bg-main-dark">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $dayNames; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $dn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <th
                                        class="py-1.5 text-[0.65rem] font-semibold text-center uppercase tracking-wider <?php echo e($i === 6 ? 'text-red-500 font-bold' : 'text-gray-400 dark:text-gray-500'); ?>">
                                        <?php echo e($dn); ?>

                                    </th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $row; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cell): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <td class="p-1 text-center align-middle h-9">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cell !== null): ?>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cell['isHoliday']): ?>
                                                    <button type="button" class="<?php echo e($cell['class']); ?>" title="<?php echo e($cell['tooltip']); ?>"
                                                        wire:click="mountAction('viewHoliday', { holiday_id: <?php echo e($cell['holiday_id']); ?> })"><?php echo e($cell['day']); ?></button>
                                                <?php else: ?>
                                                    <span class="<?php echo e($cell['class']); ?>"><?php echo e($cell['day']); ?></span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <div class="w-full mt-6 overflow-hidden border rounded-2xl border-border-light dark:border-border-dark">
        <div
            class="flex items-center gap-2 px-6 py-4 font-semibold text-black border-b bg-secondary-light dark:bg-secondary-dark border-border-light dark:border-border-dark dark:text-white">
            Daftar Hari Libur <?php echo e($year); ?>

        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($holidays) > 0): ?>
            <div class="w-full overflow-x-auto">
                <table class="w-full divide-y divide-border-light dark:divide-border-dark">
                    <thead class="bg-secondary-light dark:bg-secondary-dark">
                        <tr>
                            <th scope="col" class="p-4 text-sm font-semibold text-left text-black dark:text-white w-14">#
                            </th>
                            <th scope="col" class="p-4 text-sm font-semibold text-left text-black dark:text-white">Nama Hari
                                Libur</th>
                            <th scope="col" class="p-4 text-sm font-semibold text-left text-black dark:text-white">Tanggal
                            </th>
                            <th scope="col" class="p-4 text-sm font-semibold text-left text-black dark:text-white">Hari</th>
                            <th scope="col" class="p-4 text-sm font-semibold text-left text-black dark:text-white">
                                Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y bg-main-light divide-border-light dark:bg-main-dark dark:divide-border-dark">
                        <?php $no = 1; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $holidays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dateStr => $holiday): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $carbon = \Carbon\Carbon::parse($dateStr); ?>
                            <tr class="transition-colors cursor-pointer hover:bg-secondary-light dark:hover:bg-secondary-dark"
                                wire:click="mountAction('viewHoliday', { holiday_id: <?php echo e($holiday['id']); ?> })">
                                <td class="p-4 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                    <?php echo e($no++); ?>

                                </td>
                                <td class="p-4 text-sm font-medium text-black dark:text-white">
                                    <?php echo e($holiday['name']); ?>

                                </td>
                                <td class="p-4 text-sm text-black dark:text-white whitespace-nowrap">
                                    <?php echo e($carbon->translatedFormat('d F Y')); ?>

                                </td>
                                <td class="p-4 text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo e($carbon->translatedFormat('l')); ?>

                                </td>
                                <td class="p-4 text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo e($holiday['description'] ?? '—'); ?>

                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div
                class="flex flex-col items-center justify-center gap-3 p-8 text-gray-500 dark:text-gray-400 bg-main-light dark:bg-main-dark">
                <div
                    class="flex items-center justify-center p-4 rounded-full bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-calendar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-10 h-10 text-gray-400 dark:text-gray-500']); ?>
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
                <h2 class="text-base font-medium text-gray-600 dark:text-gray-400">Belum ada hari libur ditambahkan</h2>
                <p class="text-sm">Tidak ada jadwal hari libur untuk tahun <?php echo e($year); ?></p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
<?php endif; ?><?php /**PATH C:\laragon\www\erp-app-main\resources\views/filament/pages/hr/holiday-calendar.blade.php ENDPATH**/ ?>