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
    <?php $__env->startPush('styles'); ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <style>
            .button-spinner {
                display: inline-block;
                vertical-align: middle;
            }

            .button-spinner svg {
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            /* Custom CSS untuk map popup styling agar gambar rapi */
            .leaflet-popup-content-wrapper {
                border-radius: 12px;
            }

            .leaflet-popup-content {
                margin: 12px;
            }
        </style>
    <?php $__env->stopPush(); ?>

    
    
    
    <div class="w-full" wire:ignore.self>

        <?php
            $historyMapData = null;
            if (isset($attendanceToday) && $attendanceToday) {
                $historyMapData = [
                    'in' => $attendanceToday->clock_in && $attendanceToday->latitude_in ? [
                        'time' => \Carbon\Carbon::parse($attendanceToday->clock_in)->format('H:i'),
                        'lat' => $attendanceToday->latitude_in,
                        'lng' => $attendanceToday->longitude_in,
                        'photo' => $attendanceToday->face_snapshot_in ? asset('storage/' . $attendanceToday->face_snapshot_in) : null
                    ] : null,
                    'out' => $attendanceToday->clock_out && $attendanceToday->latitude_out ? [
                        'time' => \Carbon\Carbon::parse($attendanceToday->clock_out)->format('H:i'),
                        'lat' => $attendanceToday->latitude_out,
                        'lng' => $attendanceToday->longitude_out,
                        'photo' => $attendanceToday->face_snapshot_out ? asset('storage/' . $attendanceToday->face_snapshot_out) : null
                    ] : null
                ];
            }
        ?>

        <main class="max-w-6xl">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                <div class="md:col-span-7">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$hasCheckedIn): ?>
                        <form action="<?php echo e(route('attendance.clockin')); ?>" method="POST"
                            class="p-6 fi-section rounded-2xl ring-1" aria-labelledby="clock-in-title">
                            <?php echo csrf_field(); ?>
                            <h2 id="clock-in-title" class="text-base font-medium">Siap presensi masuk?</h2>

                            <div class="grid grid-cols-1 gap-6 pt-6 md:gap-4">
                                
                                <div class="p-4 text-center border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark"
                                    id="camera-box">

                                    <!-- INITIAL / EMPTY STATE -->
                                    <div id="camera-placeholder"
                                        class="flex flex-col items-center justify-center h-[230px] gap-2 py-4">
                                        <button type="button" id="openCameraBtn">
                                            <div
                                                class="flex items-center justify-center rounded-full bg-secondary-light hover:bg-accent-light dark:bg-secondary-dark w-14 h-14 dark:hover:bg-main-dark ring-1 ring-border-light dark:ring-border-dark">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                                </svg>
                                            </div>
                                        </button>
                                        <p class="px-4 pt-2 pb-1 text-sm font-semibold">Klik untuk membuka kamera</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Pastikan wajah Anda terlihat
                                            jelas dalam bingkai.</p>
                                    </div>

                                    <!-- CAMERA LIVE -->
                                    <div id="camera-live" class="hidden">
                                        <video id="camera-video" class="w-full rounded-2xl aspect-[4/3] bg-black" autoplay
                                            playsinline></video>
                                        <button type="button" id="captureBtn" aria-label="Ambil Foto" class="mt-4">
                                            <div
                                                class="flex items-center justify-center rounded-full w-14 h-14 bg-secondary-light hover:bg-accent-light dark:bg-secondary-dark dark:hover:bg-main-dark ring-1 ring-border-light dark:ring-border-dark">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>

                                    <!-- PHOTO PREVIEW -->
                                    <div id="camera-preview" class="hidden">
                                        <img id="photo-preview" class="object-cover w-full rounded-2xl" alt="Preview photo">
                                        <div class="flex justify-center gap-4 my-4">
                                            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','id' => 'retakeBtn','color' => 'gray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','id' => 'retakeBtn','color' => 'gray']); ?>Ambil
                                                Ulang <?php echo $__env->renderComponent(); ?>
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

                                    <!-- HIDDEN CANVAS -->
                                    <canvas id="camera-canvas" class="hidden"></canvas>
                                    <input type="hidden" name="face_snapshot" id="faceSnapshot">
                                </div>

                                <div>
                                    <div class="grid gap-4">
                                        <div class="flex flex-col gap-1">
                                            <label for="note" class="mb-2 text-sm font-semibold">Catatan
                                                (opsional)</label>
                                            <textarea id="note" name="note" rows="3"
                                                class="w-full px-3 py-2 text-sm border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                                placeholder="Tambahkan catatan..."></textarea>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','id' => 'presensiMasukButton','ariaLabel' => 'Presensi sekarang','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','id' => 'presensiMasukButton','aria-label' => 'Presensi sekarang','color' => 'primary']); ?>
                                                <span>Tandai Lokasi</span>
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

                                            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','color' => 'danger','ariaLabel' => 'Ambil Ulang Lokasi','class' => 'hidden','id' => 'retakeLocationInBtn']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','color' => 'danger','aria-label' => 'Ambil Ulang Lokasi','class' => 'hidden','id' => 'retakeLocationInBtn']); ?>
                                                <span>Ambil Ulang Lokasi</span>
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
                                </div>
                            </div>

                            <input type="hidden" name="lat" id="lat" />
                            <input type="hidden" name="lng" id="lng" />
                        </form>
                    <?php elseif($hasCheckedIn && !$hasCheckedOut): ?>
                        <form action="<?php echo e(route('attendance.clockout')); ?>" method="POST"
                            class="p-6 fi-section rounded-2xl ring-1" aria-labelledby="clock-out-title" id="clockout-form">
                            <?php echo csrf_field(); ?>
                            <h2 id="clock-out-title" class="text-base font-semibold">Sudah selesai kerja?</h2>

                            <div class="grid grid-cols-1 gap-6 pt-6 md:gap-4">
                                
                                <div class="p-4 text-center border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark"
                                    id="camera-box-out">

                                    <!-- INITIAL / EMPTY STATE -->
                                    <div id="camera-placeholder-out"
                                        class="flex flex-col items-center justify-center h-[230px] gap-2 py-4">
                                        <button type="button" id="openCameraBtnOut">
                                            <div
                                                class="flex items-center justify-center rounded-full bg-secondary-light hover:bg-accent-light dark:bg-secondary-dark w-14 h-14 dark:hover:bg-main-dark ring-1 ring-border-light dark:ring-border-dark">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                                </svg>
                                            </div>
                                        </button>
                                        <p class="px-4 pt-2 pb-1 text-sm font-semibold">Klik untuk membuka kamera</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Pastikan wajah Anda terlihat
                                            jelas dalam bingkai.</p>
                                    </div>

                                    <!-- CAMERA LIVE -->
                                    <div id="camera-live-out" class="hidden">
                                        <video id="camera-video-out" class="w-full rounded-2xl aspect-[4/3] bg-black"
                                            autoplay playsinline></video>
                                        <button type="button" id="captureBtnOut" aria-label="Ambil Foto" class="mt-4">
                                            <div
                                                class="flex items-center justify-center rounded-full w-14 h-14 bg-secondary-light hover:bg-accent-light dark:bg-secondary-dark dark:hover:bg-main-dark ring-1 ring-border-light dark:ring-border-dark">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>

                                    <!-- PHOTO PREVIEW -->
                                    <div id="camera-preview-out" class="hidden">
                                        <img id="photo-preview-out" class="object-cover w-full rounded-2xl"
                                            alt="Preview photo">
                                        <div class="flex justify-center gap-4 my-4">
                                            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','id' => 'retakeBtnOut','color' => 'gray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','id' => 'retakeBtnOut','color' => 'gray']); ?>Ambil
                                                Ulang <?php echo $__env->renderComponent(); ?>
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

                                    <!-- HIDDEN CANVAS -->
                                    <canvas id="camera-canvas-out" class="hidden"></canvas>
                                    <input type="hidden" name="face_snapshot" id="faceSnapshotOut">
                                </div>

                                <div>
                                    <div class="grid gap-4">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($attendanceToday): ?>
                                            <div
                                                class="flex items-center justify-between p-4 border border-border-light rounded-2xl bg-main-light dark:bg-accent-dark dark:border-border-dark h-[76px]">
                                                <div>
                                                    <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Jam masuk</p>
                                                    <p class="text-sm font-medium">
                                                        <?php echo e(\Carbon\Carbon::parse($attendanceToday->clock_in)->format('H:i')); ?>

                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                                                    <p class="text-sm font-medium">
                                                        <?php echo e(ucfirst($attendanceToday->status)); ?>

                                                    </p>
                                                </div>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                        <div class="flex items-center gap-2">
                                            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','color' => 'success','ariaLabel' => 'Presensi keluar sekarang','id' => 'presensiKeluarButton']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','color' => 'success','aria-label' => 'Presensi keluar sekarang','id' => 'presensiKeluarButton']); ?>
                                                <span>Presensi Keluar</span>
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

                                            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','color' => 'danger','ariaLabel' => 'Ambil Ulang Lokasi','class' => 'hidden','id' => 'retakeLocationOutBtn']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','color' => 'danger','aria-label' => 'Ambil Ulang Lokasi','class' => 'hidden','id' => 'retakeLocationOutBtn']); ?>
                                                <span>Ambil Ulang Lokasi</span>
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
                                </div>
                            </div>

                            <input type="hidden" name="lat" id="lat-out" />
                            <input type="hidden" name="lng" id="lng-out" />
                        </form>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center p-8 fi-section rounded-2xl ring-1">
                            <svg class="w-12 h-12 mb-4 text-main-primary" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                            </svg>
                            <h3 class="mb-2 text-xl font-bold text-main-primary">Presensi Selesai</h3>
                            <p class="mb-6 text-sm text-center text-gray-500 dark:text-gray-400">Kamu sudah
                                menyelesaikan
                                presensi hari ini. Terima kasih atas kerja kerasmu!</p>
                            <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => '/hr/attendance-history','color' => 'primary','icon' => 'heroicon-o-clock']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => '/hr/attendance-history','color' => 'primary','icon' => 'heroicon-o-clock']); ?>
                                Lihat Riwayat Presensi
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
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="grid grid-cols-1 gap-6 md:col-span-5">
                    <div class="flex items-center gap-4 p-6 fi-section rounded-2xl">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-full bg-secondary-light dark:bg-secondary-dark ring-1 ring-border-light dark:ring-border-dark">
                            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-clock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-6 h-6 text-gray-700 dark:text-gray-500']); ?>
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
                        <div class="text-left">
                            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400" id="current-date"></p>
                            <p class="text-lg font-medium" id="current-time"></p>
                        </div>
                    </div>

                    <div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee): ?>
                            <div class="p-6 fi-section rounded-2xl ring-1">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 overflow-hidden rounded-full bg-slate-100">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->photo): ?>
                                            <img src="<?php echo e(asset('storage/' . $employee->photo)); ?>" alt="Foto"
                                                class="object-cover w-12 h-12 rounded-full">
                                        <?php else: ?>
                                            <img src="<?php echo e(url('/assets/placeholder.jpg')); ?>" alt="Foto"
                                                class="object-cover w-12 h-12 rounded-full">
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                    <div>
                                        <h2 class="text-base font-semibold leading-6"><?php echo e($employee->full_name); ?></h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($employee->position ?? '-'); ?>

                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 mt-6 text-sm">
                                    <div
                                        class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                        <dt class="text-gray-500 dark:text-gray-400">Departemen</dt>
                                        <dd class="font-medium"><?php echo e($employee->department->name ?? '-'); ?></dd>
                                    </div>
                                    <div
                                        class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                        <dt class="text-gray-500 dark:text-gray-400">Kantor</dt>
                                        <dd class="font-medium"><?php echo e($office->name ?? '-'); ?></dd>
                                    </div>
                                    <div
                                        class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                        <dt class="text-gray-500 dark:text-gray-400">Jadwal</dt>
                                        <dd class="font-medium">
                                            <?php echo e($employee->shift->name ?? '-'); ?>

                                            (<?php echo e(isset($employee->shift->start_time) ? \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i') : '-'); ?>

                                            -
                                            <?php echo e(isset($employee->shift->end_time) ? \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i') : '-'); ?>)
                                        </dd>
                                    </div>
                                    <div
                                        class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                        <dt class="text-gray-500 dark:text-gray-400">Tipe Karyawan</dt>
                                        <dd class="font-medium">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->can_wfa == 1): ?> Bekerja dimana saja <?php else: ?> Bekerja dari kantor
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?> &
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->can_unlock_shift == 1): ?> Jam kerja fleksibel <?php else: ?> Jam kerja tetap
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </dd>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-6 text-center text-gray-500 fi-section rounded-2xl ring-1">
                                Tidak ada data karyawan untuk user ini.
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    
                    <div>
                        <div class="p-6 fi-section rounded-2xl ring-1">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h2 class="text-base font-medium">Lokasi & Riwayat Presensi</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Verifikasi lokasi Anda sebelum
                                        presensi dan lihat riwayat hari ini.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-1 text-xs font-medium text-gray-500 border rounded-md border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark"
                                        id="accuracy-badge">~</span>
                                </div>
                            </div>

                            
                            <div id="map-container"
                                class="relative overflow-hidden border rounded-2xl border-slate-200">

                                <div id="map" class="relative h-[350px] w-full bg-slate-100 z-10"></div>

                                
                                <div id="map-loading-overlay"
                                    class="absolute inset-0 bg-white/70 dark:bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[1000] transition-all">
                                    <div class="flex flex-col items-center gap-3">
                                        <div
                                            class="w-10 h-10 border-4 rounded-full border-primary-500 border-t-transparent animate-spin">
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200"
                                            id="map-loading-text">Memuat peta...</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 mt-4 text-sm" id="location-info">
                                <div
                                    class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                    <p class="text-gray-500 dark:text-gray-400">Alamat</p>
                                    <p class="font-medium" id="address-text">-</p>
                                </div>
                                <div
                                    class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                    <p class="text-gray-500 dark:text-gray-400">Koordinat</p>
                                    <p class="font-medium" id="coords-text">-</p>
                                </div>
                                <div
                                    class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                    <p class="text-gray-500 dark:text-gray-400">Status</p>
                                    <p class="font-medium" id="status-text">-</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>

        
        <div id="image-preview-modal"
            class="fixed inset-0 z-[2000] hidden items-center justify-center bg-black/70 backdrop-blur-sm">
            <div class="relative w-full max-w-3xl mx-4">
                <button id="close-image-modal"
                    class="absolute flex items-center justify-center w-8 h-8 text-gray-800 bg-white rounded-full shadow -top-3 -right-3 hover:bg-gray-200">
                    ✕
                </button>
                <img id="image-preview-content" src=""
                    class="w-full max-h-[80vh] object-contain rounded-lg shadow-xl bg-black" />
            </div>
        </div>

    </div>
    

    <?php $__env->startPush('scripts'); ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            let map = null;
            let userMarker = null;

            // Integrasi agar jalan mulus di Livewire Navigation
            document.addEventListener("livewire:initialized", initAttendancePage);
            document.addEventListener("livewire:navigated", initAttendancePage);

            function initAttendancePage() {
                const mapEl = document.getElementById("map");
                if (!mapEl) return;

                if (map) {
                    map.remove();
                    map = null;
                }

                // ============================================================
                // CONFIG & CONSTANTS
                // ============================================================
                const OFFICE_CENTER = [<?php echo e($employee->office->latitude); ?>, <?php echo e($employee->office->longitude); ?>];
                const OFFICE_RADIUS = <?php echo e($office->radius_meters); ?>;
                const EMP_PHOTO = "<?php echo e($employee->photo ? asset('storage/' . $employee->photo) : url('/assets/placeholder.jpg')); ?>";
                const EMP_NAME = "<?php echo e($employee->full_name); ?>";
                const HISTORY_DATA = <?php echo json_encode($historyMapData, 15, 512) ?>;

                const GEO_OPTIONS = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                };
                const GOOD_ACCURACY_METERS = 100;
                const MIN_SPEED_FAKE_LIMIT = 50;

                // Inisialisasi Peta
                map = L.map('map').setView(OFFICE_CENTER, 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                setTimeout(() => map.invalidateSize(), 500);

                // Lingkaran Radius Kantor
                L.circle(OFFICE_CENTER, {
                    color: '#2563eb', weight: 2,
                    fillColor: '#3b82f6', fillOpacity: 0.15,
                    radius: OFFICE_RADIUS
                }).addTo(map);

                const addressText = document.getElementById('address-text');
                const coordsText = document.getElementById('coords-text');
                const statusText = document.getElementById('status-text');
                const accuracyBadge = document.getElementById('accuracy-badge');

                const mapLoadingOverlay = document.getElementById('map-loading-overlay');
                const mapLoadingText = document.getElementById('map-loading-text');

                function showMapLoading(text = "Mencari lokasi...") {
                    if (mapLoadingText && mapLoadingOverlay) {
                        mapLoadingText.innerText = text;
                        mapLoadingOverlay.classList.remove('hidden');
                        mapLoadingOverlay.classList.add('flex');
                    }
                }

                function hideMapLoading() {
                    if (mapLoadingOverlay) {
                        mapLoadingOverlay.classList.add('hidden');
                        mapLoadingOverlay.classList.remove('flex');
                    }
                }

                // ============================================================
                // MENAMPILKAN RIWAYAT DI PETA (HISTORY MARKERS)
                // ============================================================
                function loadHistoryMarkers() {
                    if (!HISTORY_DATA) return;
                    if (HISTORY_DATA.in) addHistoryMarker(HISTORY_DATA.in, 'Masuk', '#10b981');
                    if (HISTORY_DATA.out) addHistoryMarker(HISTORY_DATA.out, 'Keluar', '#f43f5e');
                }

                function addHistoryMarker(data, type, color) {
                    const icon = L.divIcon({
                        html: `
                                                                                                                <div style="width: 35px; height: 35px; background: ${color}; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.4); border: 2px solid white;">
                                                                                                                   <span style="color: white; font-weight: bold; font-size: 10px;">${type === 'Masuk' ? 'IN' : 'OUT'}</span>
                                                                                                                </div>`,
                        className: "",
                        iconSize: [35, 35],
                        iconAnchor: [17, 35],
                    });

                    const marker = L.marker([data.lat, data.lng], { icon }).addTo(map);

                    marker.bindPopup(`
                                                                                                            <div class="text-sm text-gray-800" style="width: 250px !important;">
                                                                                                                <div class="flex items-center gap-3 mb-2">
                                                                                                                    <img src="${EMP_PHOTO}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc;" />
                                                                                                                    <div>
                                                                                                                        <strong>${EMP_NAME}</strong><br>
                                                                                                                        <span class="text-xs text-gray-500">Titik Presensi ${type}</span>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <hr class="my-1 border-gray-200" />
                                                                                                                <div class="mt-2">
                                                                                                                    <span class="block mb-1"><b>Waktu:</b> ${data.time}</span>
                                                                                                                    <b>Foto:</b><br>
                                                                                                                    ${data.photo ?
                            `<img src="${data.photo}" class="mt-1 transition cursor-pointer attendance-image hover:opacity-80" style="width: 100%; height: 140px; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb;" data-src="${data.photo}" />`
                            : '<span class="text-xs text-gray-400">Tidak tersedia</span>'
                        }
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        `);
                }

                loadHistoryMarkers();

                // ============================================================
                // UTILITY MAP UPDATES & REVERSE GEOCODING
                // ============================================================
                async function updateMapUI(lat, lng) {
                    if (userMarker) map.removeLayer(userMarker);

                    const currentIcon = L.divIcon({
                        html: `<div style="width: 20px; height: 20px; background: #3b82f6; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.5);"></div>`,
                        className: "", iconSize: [20, 20], iconAnchor: [10, 10],
                    });

                    userMarker = L.marker([lat, lng], { icon: currentIcon }).addTo(map);
                    map.setView([lat, lng], 17);

                    if (coordsText) coordsText.innerText = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    if (addressText) addressText.innerText = 'Memuat alamat...';
                    if (statusText) statusText.innerText = 'Memverifikasi...';

                    const address = await getAddress(lat, lng);
                    if (addressText) addressText.innerText = address;

                    const distance = map.distance([lat, lng], OFFICE_CENTER);
                    const inside = distance <= OFFICE_RADIUS;
                    if (statusText) {
                        statusText.innerText = inside
                            ? `✅ Dalam radius (${Math.round(distance)}m)`
                            : `❌ Luar radius (${Math.round(distance)}m)`;
                    }
                }

                async function getAddress(lat, lng) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);
                        const data = await res.json();
                        return data.display_name || 'Alamat tidak ditemukan';
                    } catch {
                        return 'Gagal mengambil alamat';
                    }
                }

                function setButtonState(btn, text, { loading = false, disabled = false } = {}) {
                    if (!btn) return;
                    btn.disabled = loading || disabled;
                    const span = btn.querySelector('span');
                    if (span) span.textContent = text;
                    let spinner = btn.querySelector('.button-spinner');
                    if (loading && !spinner) {
                        spinner = document.createElement('span');
                        spinner.className = 'button-spinner ml-2 inline-flex items-center';
                        spinner.innerHTML = `<svg class="w-4 h-4 text-current animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>`;
                        btn.appendChild(spinner);
                    } else if (!loading && spinner) {
                        spinner.remove();
                    }
                }

                function getVerifiedPosition() {
                    return new Promise((resolve, reject) => {
                        if (!navigator.geolocation) return reject(new Error('GPS tidak didukung browser.'));

                        let bestPosition = null;
                        let watchId;

                        const fallbackTimeout = setTimeout(() => {
                            navigator.geolocation.clearWatch(watchId);
                            if (bestPosition) resolve(bestPosition);
                            else reject(new Error('Gagal mendapatkan lokasi. Coba reload atau cek izin GPS.'));
                        }, GEO_OPTIONS.timeout);

                        watchId = navigator.geolocation.watchPosition(
                            (pos) => {
                                const { latitude, longitude, accuracy, speed } = pos.coords;
                                if (speed !== null && speed > MIN_SPEED_FAKE_LIMIT) {
                                    clearTimeout(fallbackTimeout); navigator.geolocation.clearWatch(watchId);
                                    return reject(new Error(`Terdeteksi Fake GPS.`));
                                }
                                if (!bestPosition || accuracy < bestPosition.accuracy) {
                                    bestPosition = pos.coords;
                                }
                                if (accuracy <= GOOD_ACCURACY_METERS) {
                                    clearTimeout(fallbackTimeout); navigator.geolocation.clearWatch(watchId);
                                    resolve(bestPosition);
                                }
                            },
                            (err) => {
                                if (!bestPosition) {
                                    clearTimeout(fallbackTimeout); navigator.geolocation.clearWatch(watchId);
                                    reject(new Error(err.code === 1 ? 'Izin lokasi ditolak.' : 'Sinyal GPS lemah.'));
                                }
                            },
                            GEO_OPTIONS
                        );
                    });
                }

                // ============================================================
                // CAMERA SETUP
                // ============================================================
                function initCamera({ boxId, placeholderId, liveId, previewId, videoId, canvasId, photoPreviewId, snapshotId, openBtnId, captureBtnId, retakeBtnId }) {
                    if (!document.getElementById(boxId)) return null;

                    const placeholder = document.getElementById(placeholderId);
                    const cameraLive = document.getElementById(liveId);
                    const cameraPreview = document.getElementById(previewId);
                    const video = document.getElementById(videoId);
                    const canvas = document.getElementById(canvasId);
                    const photoPreview = document.getElementById(photoPreviewId);
                    const snapshot = document.getElementById(snapshotId);
                    let stream = null;

                    async function openCamera() {
                        try {
                            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                            video.srcObject = stream;
                            placeholder.classList.add('hidden');
                            cameraLive.classList.remove('hidden');
                        } catch (err) { alert('Gagal buka kamera: ' + err.message); }
                    }

                    function capturePhoto() {
                        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                        canvas.getContext('2d').drawImage(video, 0, 0);
                        const imageData = canvas.toDataURL('image/jpeg');
                        photoPreview.src = imageData; snapshot.value = imageData;
                        stopCamera();
                        cameraLive.classList.add('hidden'); cameraPreview.classList.remove('hidden');
                    }

                    function stopCamera() { if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; } }
                    function retakePhoto() { cameraPreview.classList.add('hidden'); placeholder.classList.remove('hidden'); snapshot.value = ''; photoPreview.src = ''; }

                    document.getElementById(openBtnId)?.addEventListener('click', openCamera);
                    document.getElementById(captureBtnId)?.addEventListener('click', capturePhoto);
                    document.getElementById(retakeBtnId)?.addEventListener('click', retakePhoto);

                    return { getSnapshot: () => snapshot.value };
                }

                // ============================================================
                // ALUR TANDAI LOKASI (Berlaku Masuk / Keluar)
                // ============================================================
                async function handleTagLocation({ btn, latInput, lngInput, getSnapshot, onReady, labelInit }) {
                    if (!getSnapshot()) { alert('Silakan ambil foto terlebih dahulu.'); return; }

                    setButtonState(btn, 'Mencari Lokasi...', { loading: true });
                    document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });

                    showMapLoading("Mengambil titik lokasi perangkat...");

                    try {
                        const pos = await getVerifiedPosition();

                        showMapLoading("Memastikan akurasi lokasi...");
                        await updateMapUI(pos.latitude, pos.longitude);

                        latInput.value = pos.latitude;
                        lngInput.value = pos.longitude;

                        if (accuracyBadge) {
                            accuracyBadge.innerText = `Akurasi: ±${Math.round(pos.accuracy)}m`;
                            accuracyBadge.className = "px-2 py-1 text-xs font-medium border rounded-md " + (pos.accuracy < 30 ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700");
                        }

                        hideMapLoading();
                        onReady(pos.latitude, pos.longitude, pos.accuracy);

                    } catch (err) {
                        hideMapLoading();
                        const retry = confirm(`${err.message}\n\nIngin coba lagi?`);
                        if (retry) {
                            setButtonState(btn, labelInit);
                            handleTagLocation({ btn, latInput, lngInput, getSnapshot, onReady, labelInit });
                        } else {
                            setButtonState(btn, labelInit);
                        }
                    }
                }

                // ============================================================
                // CLOCK-IN LOGIC
                // ============================================================
                const clockInForm = document.querySelector('form[action="<?php echo e(route('attendance.clockin')); ?>"]');
                const btnMasuk = document.getElementById('presensiMasukButton');
                const cameraIn = initCamera({
                    boxId: 'camera-box', placeholderId: 'camera-placeholder', liveId: 'camera-live', previewId: 'camera-preview', videoId: 'camera-video', canvasId: 'camera-canvas', photoPreviewId: 'photo-preview', snapshotId: 'faceSnapshot', openBtnId: 'openCameraBtn', captureBtnId: 'captureBtn', retakeBtnId: 'retakeBtn'
                });
                let clockInLocationReady = false;

                if (btnMasuk && clockInForm) {
                    // Gunakan .onclick untuk mencegah duplikasi event listener di Livewire
                    btnMasuk.onclick = (e) => {
                        e.preventDefault();
                        if (btnMasuk.disabled) return; // Mencegah double click saat sedang proses

                        if (clockInLocationReady) {
                            setButtonState(btnMasuk, 'Mengirim...', { loading: true });
                            clockInForm.submit();
                            return;
                        }

                        handleTagLocation({
                            btn: btnMasuk, latInput: document.getElementById('lat'), lngInput: document.getElementById('lng'), getSnapshot: () => cameraIn?.getSnapshot() ?? '', labelInit: 'Tandai Lokasi',
                            onReady: () => { clockInLocationReady = true; setButtonState(btnMasuk, 'Presensi Masuk'); document.getElementById('retakeLocationInBtn')?.classList.remove('hidden'); }
                        });
                    };

                    const retakeLocInBtn = document.getElementById('retakeLocationInBtn');
                    if (retakeLocInBtn) {
                        retakeLocInBtn.onclick = (e) => {
                            e.preventDefault();
                            clockInLocationReady = false; document.getElementById('lat').value = ''; document.getElementById('lng').value = ''; setButtonState(btnMasuk, 'Tandai Lokasi'); retakeLocInBtn.classList.add('hidden');
                        };
                    }
                }

                // ============================================================
                // CLOCK-OUT LOGIC
                // ============================================================
                const clockOutForm = document.getElementById('clockout-form');
                const btnKeluar = document.getElementById('presensiKeluarButton');
                const cameraOut = initCamera({
                    boxId: 'camera-box-out', placeholderId: 'camera-placeholder-out', liveId: 'camera-live-out', previewId: 'camera-preview-out', videoId: 'camera-video-out', canvasId: 'camera-canvas-out', photoPreviewId: 'photo-preview-out', snapshotId: 'faceSnapshotOut', openBtnId: 'openCameraBtnOut', captureBtnId: 'captureBtnOut', retakeBtnId: 'retakeBtnOut'
                });
                let clockOutLocationReady = false;

                if (btnKeluar && clockOutForm) {
                    // Gunakan .onclick untuk mencegah duplikasi event listener
                    btnKeluar.onclick = (e) => {
                        e.preventDefault();
                        if (btnKeluar.disabled) return; // Mencegah double click saat sedang proses

                        if (clockOutLocationReady) {
                            setButtonState(btnKeluar, 'Mengirim...', { loading: true });
                            clockOutForm.submit();
                            return;
                        }

                        handleTagLocation({
                            btn: btnKeluar, latInput: document.getElementById('lat-out'), lngInput: document.getElementById('lng-out'), getSnapshot: () => cameraOut?.getSnapshot() ?? '', labelInit: 'Tandai Lokasi',
                            onReady: () => { clockOutLocationReady = true; setButtonState(btnKeluar, 'Presensi Keluar'); document.getElementById('retakeLocationOutBtn')?.classList.remove('hidden'); }
                        });
                    };

                    const retakeLocOutBtn = document.getElementById('retakeLocationOutBtn');
                    if (retakeLocOutBtn) {
                        retakeLocOutBtn.onclick = (e) => {
                            e.preventDefault();
                            clockOutLocationReady = false; document.getElementById('lat-out').value = ''; document.getElementById('lng-out').value = ''; setButtonState(btnKeluar, 'Tandai Lokasi'); retakeLocOutBtn.classList.add('hidden');
                        };
                    }
                }
            }

            // ============================================================
            // MODAL PREVIEW FOTO (Global Event Handler)
            // ============================================================
            document.addEventListener('click', function (e) {
                const img = e.target.closest('.attendance-image');
                if (!img) return;

                const modal = document.getElementById('image-preview-modal');
                const modalImg = document.getElementById('image-preview-content');

                if (modal && modalImg) {
                    modalImg.src = img.dataset.src;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            });

            document.getElementById('close-image-modal')?.addEventListener('click', closeImageModal);
            document.getElementById('image-preview-modal')?.addEventListener('click', function (e) {
                if (e.target.id === 'image-preview-modal') closeImageModal();
            });

            function closeImageModal() {
                const modal = document.getElementById('image-preview-modal');
                const modalImg = document.getElementById('image-preview-content');
                if (modal && modalImg) {
                    modalImg.src = '';
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            }

            function updateDateTimeClockIn() {
                const now = new Date();

                // Format tanggal
                const days = [
                    'Minggu', 'Senin', 'Selasa', 'Rabu',
                    'Kamis', 'Jumat', 'Sabtu'
                ];

                const months = [
                    'Januari', 'Februari', 'Maret', 'April',
                    'Mei', 'Juni', 'Juli', 'Agustus',
                    'September', 'Oktober', 'November', 'Desember'
                ];

                const dayName = days[now.getDay()];
                const date = now.getDate();
                const month = months[now.getMonth()];
                const year = now.getFullYear();

                document.getElementById('current-date').textContent =
                    `${dayName}, ${date} ${month} ${year}`;

                // Format jam
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');
                const ampm = now.getHours() >= 12 ? 'PM' : 'AM';

                document.getElementById('current-time').textContent =
                    `${hours}:${minutes}:${seconds} ${ampm}`;
            }

            setInterval(updateDateTimeClockIn, 1000);
            updateDateTimeClockIn();
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
<?php endif; ?><?php /**PATH /var/www/erp-app-main/resources/views/filament/pages/hr/attendance.blade.php ENDPATH**/ ?>