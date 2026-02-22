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
    <div class="space-y-8">

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
             <?php $__env->slot('heading', null, []); ?> 
                Project Timeline
             <?php $__env->endSlot(); ?>

            <div class="w-full">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($this->ganttData['data']) > 0): ?>
                    <?php
                        $projectCount = count($ganttData['data']);
                        $minHeight = 400;
                        $rowHeight = 40;
                        $headerHeight = 80;
                        $calculatedHeight = max($minHeight, $projectCount * $rowHeight + $headerHeight);
                    ?>
                    <div id="gantt_here" style="width:100%; height:<?php echo e($calculatedHeight); ?>px;"></div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-64 text-center text-gray-500">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mb-2 text-lg font-medium text-white">Tidak ada project yang tersedia</h3>
                        <p class="text-sm">Tambahkan tanggal mulai dan selesai pada project untuk melihat timeline</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
             <?php $__env->slot('heading', null, []); ?> 
                Keterangan Status
             <?php $__env->endSlot(); ?>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded" style="background-color: #3b82f6;"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Sedang Dikerjakan</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded" style="background-color: #10b981;"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Hampir Selesai</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded" style="background-color: #f59e0b;"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Mendekati Deadline</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded" style="background-color: #ef4444;"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Terlambat</span>
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

    </div>

    <?php $__env->startPush('styles'); ?>
        <link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css" type="text/css">
        <link rel="stylesheet" href="<?php echo e(asset('css/gantt-timeline.css')); ?>" type="text/css">
        <style>
            /* Today marker line styling */
            .gantt_marker.today {
                background-color: #EF4444 !important;
                /* Red color for today line */
                opacity: 0.8;
                z-index: 10;
            }

            .gantt_marker.today .gantt_marker_content {
                background-color: #EF4444 !important;
                color: white !important;
                font-weight: bold;
                font-size: 12px;
                padding: 2px 6px;
                border-radius: 4px;
                white-space: nowrap;
            }
        </style>
    <?php $__env->stopPush(); ?>

    <?php $__env->startPush('scripts'); ?>
        <script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>
        <script>
            let ganttPageInitialized = false;
            let ganttData = <?php echo json_encode($ganttData ?? ['data' => [], 'links' => []], 512) ?>;

            function waitForGantt(callback) {
                if (typeof gantt !== 'undefined') {
                    callback();
                } else {
                    setTimeout(() => waitForGantt(callback), 100);
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                console.log('Page DOM ready, waiting for dhtmlxGantt...');
                waitForGantt(() => {
                    console.log('dhtmlxGantt loaded, initializing...');
                    initializeGanttPage();
                });
            });

            document.addEventListener('livewire:navigated', function () {
                console.log('Livewire navigated, reinitializing gantt...');
                if (ganttPageInitialized) {
                    gantt.clearAll();
                    ganttPageInitialized = false;
                }
                waitForGantt(() => {
                    initializeGanttPage();
                });
            });

            function initializeGanttPage() {
                try {
                    console.log('Page dhtmlxGantt data:', ganttData);

                    if (!ganttData.data || ganttData.data.length === 0) {
                        console.log('No page gantt data available');
                        return;
                    }

                    const container = document.getElementById('gantt_here');
                    if (!container) {
                        console.error('Page Gantt container not found');
                        return;
                    }

                    // ✨ Enable marker plugin for today line
                    gantt.plugins({
                        marker: true
                    });

                    gantt.config.date_format = "%d-%m-%Y %H:%i";

                    gantt.config.scales = [{
                        unit: "year",
                        step: 1,
                        format: "%Y"
                    },
                    {
                        unit: "month",
                        step: 1,
                        format: "%F"
                    }
                    ];

                    gantt.config.readonly = true;
                    gantt.config.drag_move = false;
                    gantt.config.drag_resize = false;
                    gantt.config.drag_progress = false;
                    gantt.config.drag_links = false;

                    gantt.config.grid_width = 350;
                    gantt.config.row_height = 40;
                    gantt.config.task_height = 32;
                    gantt.config.bar_height = 24;

                    gantt.config.columns = [{
                        name: "text",
                        label: "Nama Project",
                        width: 200,
                        tree: true
                    },
                    {
                        name: "status",
                        label: "Status",
                        width: 100,
                        align: "center"
                    },
                    {
                        name: "duration",
                        label: "Durasi",
                        width: 90,
                        align: "center"
                    }
                    ];

                    gantt.templates.task_class = function (start, end, task) {
                        return task.is_overdue ? "overdue" : "";
                    };

                    gantt.templates.tooltip_text = function (start, end, task) {
                        return `<b>Project:</b> ${task.text}<br/>
                                    <b>Status:</b> ${task.status}<br/>
                                    <b>Duration:</b> ${task.duration} day(s)<br/>
                                    <b>Progress:</b> ${Math.round(task.progress * 100)}%<br/>
                                    <b>Start:</b> ${gantt.templates.tooltip_date_format(start)}<br/>
                                    <b>End:</b> ${gantt.templates.tooltip_date_format(end)}
                                    ${task.is_overdue ? '<br/><b style="color: #ef4444;">⚠️ OVERDUE</b>' : ''}`;
                    };

                    if (!ganttPageInitialized) {
                        gantt.init("gantt_here");
                        ganttPageInitialized = true;
                        console.log('Gantt initialized for the first time');
                    }

                    gantt.clearAll();
                    gantt.parse(ganttData);

                    // ✨ Add today marker line
                    const today = new Date();
                    gantt.addMarker({
                        start_date: today,
                        css: "today",
                        text: "Hari Ini",
                    });

                    console.log('Page dhtmlxGantt initialized successfully with', ganttData.data.length,
                        'projects and today marker');

                } catch (error) {
                    console.error('Error initializing Page dhtmlxGantt:', error);

                    const container = document.getElementById('gantt_here');
                    if (container) {
                        container.innerHTML = `
                                <div class="flex flex-col items-center justify-center h-64 gap-4 text-red-500">
                                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="text-lg font-medium">Gagal memuat timeline</h3>
                                    <p class="text-sm">Silakan refresh halaman atau hubungi tim IT</p>
                                    <p class="text-xs">Error: ${error.message}</p>
                                </div>
                            `;
                    }
                }
            }
        </script>
    <?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\erp-app\resources\views/filament/pages/project/project-timeline.blade.php ENDPATH**/ ?>