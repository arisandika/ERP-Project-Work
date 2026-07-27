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
    <div class="bg-white p-6 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
        <div class="w-full md:w-2/3">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2 text-gray-800 dark:text-gray-200">
                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-funnel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-primary-500']); ?>
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
                Filter Periode Laporan
            </h2>

            <?php echo e($this->form); ?>

        </div>

        <div class="w-full md:w-auto flex justify-end mt-4 md:mt-0">
            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['icon' => 'heroicon-o-printer','color' => 'gray','onclick' => 'window.print()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-printer','color' => 'gray','onclick' => 'window.print()']); ?>
                Cetak Laporan
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

    <div id="print-area" class="bg-white p-8 md:p-12 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 print:shadow-none print:ring-0 print:p-0">
        <div class="text-center mb-10 pb-6 border-b-2 border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-1">
                PT. Next Generation Solutions
            </h2>

            <h1 class="text-2xl md:text-3xl font-extrabold uppercase tracking-widest text-gray-900 dark:text-white">
                Laporan Laba Rugi
            </h1>

            <p class="text-gray-500 mt-2 font-medium">
                Periode:
                <span class="text-gray-800 dark:text-gray-200">
                    <?php echo e($period); ?>

                </span>
            </p>
        </div>

        <div class="max-w-4xl mx-auto text-sm md:text-base tabular-nums">

            
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 border-gray-800 dark:border-gray-200 pb-2">
                    Pendapatan Usaha
                </h3>

                <div class="space-y-1">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $revenueDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                <?php echo e($category ?: 'Pendapatan Lain-lain'); ?>

                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span><?php echo e(number_format($items->sum('amount'), 0, ',', '.')); ?></span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada pendapatan pada periode ini.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Pendapatan</span>

                    <div class="w-48 flex justify-between">
                        <span class="text-gray-400">Rp</span>
                        <span><?php echo e(number_format($totalRevenue, 0, ',', '.')); ?></span>
                    </div>
                </div>
            </div>

            
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 border-gray-800 dark:border-gray-200 pb-2">
                    Harga Pokok Penjualan / Beban Pokok
                </h3>

                <div class="space-y-1">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $cogsDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                <?php echo e($category ?: 'HPP Lain-lain'); ?>

                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span><?php echo e(number_format($items->sum('amount'), 0, ',', '.')); ?></span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada HPP pada periode ini.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total HPP</span>

                    <div class="w-48 flex justify-between text-danger-600 dark:text-danger-400">
                        <span class="opacity-60">Rp</span>
                        <span>(<?php echo e(number_format($totalCogs, 0, ',', '.')); ?>)</span>
                    </div>
                </div>
            </div>

            
            <div class="flex justify-between items-center py-3 px-4 mb-8 bg-gray-100 dark:bg-gray-800 border-l-4 border-gray-400 font-bold text-lg rounded-r-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase">
                    Laba Kotor
                </span>

                <div class="w-48 flex justify-between <?php echo e($grossProfit >= 0 ? 'text-gray-900 dark:text-white' : 'text-danger-600 dark:text-danger-400'); ?>">
                    <span class="opacity-60">Rp</span>
                    <span>
                        <?php echo e($grossProfit < 0 ? '(' : ''); ?><?php echo e(number_format(abs($grossProfit), 0, ',', '.')); ?><?php echo e($grossProfit < 0 ? ')' : ''); ?>

                    </span>
                </div>
            </div>

            
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 border-gray-800 dark:border-gray-200 pb-2">
                    Biaya Operasional
                </h3>

                <div class="space-y-1">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $opexDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                <?php echo e($category ?: 'Biaya Operasional Lain-lain'); ?>

                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span><?php echo e(number_format($items->sum('amount'), 0, ',', '.')); ?></span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada biaya operasional pada periode ini.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Biaya Operasional</span>

                    <div class="w-48 flex justify-between text-danger-600 dark:text-danger-400">
                        <span class="opacity-60">Rp</span>
                        <span>(<?php echo e(number_format($totalOpex, 0, ',', '.')); ?>)</span>
                    </div>
                </div>
            </div>

            
            <div class="flex justify-between items-center py-3 px-4 mb-8 bg-gray-100 dark:bg-gray-800 border-l-4 border-gray-400 font-bold text-lg rounded-r-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase">
                    Laba Operasional
                </span>

                <div class="w-48 flex justify-between <?php echo e($operatingProfit >= 0 ? 'text-gray-900 dark:text-white' : 'text-danger-600 dark:text-danger-400'); ?>">
                    <span class="opacity-60">Rp</span>
                    <span>
                        <?php echo e($operatingProfit < 0 ? '(' : ''); ?><?php echo e(number_format(abs($operatingProfit), 0, ',', '.')); ?><?php echo e($operatingProfit < 0 ? ')' : ''); ?>

                    </span>
                </div>
            </div>

            
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 border-gray-800 dark:border-gray-200 pb-2">
                    Pendapatan Lain-lain
                </h3>

                <div class="space-y-1">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $otherIncomeDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                <?php echo e($category ?: 'Pendapatan Lain-lain'); ?>

                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span><?php echo e(number_format($items->sum('amount'), 0, ',', '.')); ?></span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada pendapatan lain-lain.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Pendapatan Lain-lain</span>

                    <div class="w-48 flex justify-between">
                        <span class="text-gray-400">Rp</span>
                        <span><?php echo e(number_format($totalOtherIncome, 0, ',', '.')); ?></span>
                    </div>
                </div>
            </div>

            
            <div class="mb-8">
                <h3 class="font-bold text-gray-900 dark:text-white uppercase mb-3 border-b-2 border-gray-800 dark:border-gray-200 pb-2">
                    Beban Lain-lain
                </h3>

                <div class="space-y-1">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $otherExpenseDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="flex justify-between py-1.5 px-2 rounded">
                            <span class="text-gray-700 dark:text-gray-300 pl-4">
                                <?php echo e($category ?: 'Beban Lain-lain'); ?>

                            </span>

                            <div class="w-48 flex justify-between font-medium text-gray-900 dark:text-gray-100">
                                <span class="text-gray-400">Rp</span>
                                <span><?php echo e(number_format($items->sum('amount'), 0, ',', '.')); ?></span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-gray-400 pl-4 py-1.5 italic">
                            Tidak ada beban lain-lain.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex justify-between py-2 px-2 mt-3 border-t border-gray-300 dark:border-gray-700 font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800/50 rounded-sm">
                    <span class="uppercase text-xs md:text-sm">Total Beban Lain-lain</span>

                    <div class="w-48 flex justify-between text-danger-600 dark:text-danger-400">
                        <span class="opacity-60">Rp</span>
                        <span>(<?php echo e(number_format($totalOtherExpense, 0, ',', '.')); ?>)</span>
                    </div>
                </div>
            </div>

            
            <div class="flex justify-between items-center py-3 px-4 mb-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-bold text-lg rounded-lg">
                <span class="text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                    Laba Sebelum Pajak
                </span>

                <div class="w-48 flex justify-between <?php echo e($profitBeforeTax >= 0 ? 'text-gray-900 dark:text-white' : 'text-danger-600 dark:text-danger-400'); ?>">
                    <span class="opacity-60">Rp</span>
                    <span>
                        <?php echo e($profitBeforeTax < 0 ? '(' : ''); ?><?php echo e(number_format(abs($profitBeforeTax), 0, ',', '.')); ?><?php echo e($profitBeforeTax < 0 ? ')' : ''); ?>

                    </span>
                </div>
            </div>

            
            <div class="flex justify-between py-2 px-4 mb-8 text-gray-700 dark:text-gray-300">
                <span>Pajak</span>

                <div class="w-48 flex justify-between">
                    <span class="text-gray-400">Rp</span>
                    <span><?php echo e(number_format($taxExpense, 0, ',', '.')); ?></span>
                </div>
            </div>

            
            <div class="flex justify-between items-center py-4 px-2 mt-6 border-t-2 border-b-4 border-double border-gray-900 dark:border-white font-black text-xl md:text-2xl text-gray-900 dark:text-white">
                <span class="uppercase tracking-widest">
                    Laba Bersih
                </span>

                <div class="w-56 flex justify-between <?php echo e($netProfit >= 0 ? 'text-primary-600 dark:text-primary-500' : 'text-danger-600 dark:text-danger-500'); ?>">
                    <span class="opacity-50">Rp</span>
                    <span>
                        <?php echo e($netProfit < 0 ? '(' : ''); ?><?php echo e(number_format(abs($netProfit), 0, ',', '.')); ?><?php echo e($netProfit < 0 ? ')' : ''); ?>

                    </span>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .print\:hidden {
                display: none !important;
            }

            #print-area,
            #print-area * {
                visibility: visible;
            }

            #print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 20px;
                box-shadow: none !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH /var/www/erp-app-main/resources/views/filament/pages/finance/profit-and-loss-report.blade.php ENDPATH**/ ?>