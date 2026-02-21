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
        </style>
    <?php $__env->stopPush(); ?>

    <main class="max-w-6xl">
        <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="grid grid-cols-1 gap-6 md:col-span-3">

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee): ?>
                    <div
                        class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
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
                                <h2 class="text-lg font-semibold leading-6"><?php echo e($employee->full_name); ?></h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($employee->position ?? '-'); ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-6 text-sm">
                            <div
                                class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Departemen</dt>
                                <dd class="font-medium"><?php echo e($employee->department->name ?? '-'); ?></dd>
                            </div>
                            <div
                                class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Kantor</dt>
                                <dd class="font-medium"><?php echo e($office->name ?? '-'); ?></dd>
                            </div>
                            <div
                                class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Jadwal</dt>
                                <dd class="font-medium">
                                    <?php echo e($employee->shift->name ?? '-'); ?>

                                    (<?php echo e(isset($employee->shift->start_time) ? \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i') : '-'); ?>

                                    -
                                    <?php echo e(isset($employee->shift->end_time) ? \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i') : '-'); ?>)
                                </dd>
                            </div>
                            <div
                                class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Tipe Karyawan</dt>
                                <dd class="font-medium">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->can_wfa == 1): ?>
                                        Bekerja dari rumah
                                    <?php else: ?>
                                        Bekerja dari kantor
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    &
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($employee->can_unlock_shift == 1): ?>
                                        Jam kerja fleksibel
                                    <?php else: ?>
                                        Jam kerja tetap
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </dd>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div
                        class="p-6 text-center text-gray-500 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        Tidak ada data karyawan untuk user ini.
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$hasCheckedIn): ?>
                    <form action="<?php echo e(route('attendance.clockin')); ?>" method="POST"
                        class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                        aria-labelledby="clock-in-title">
                        <?php echo csrf_field(); ?>
                        <h2 id="clock-in-title" class="text-base font-medium">Siap presensi masuk?</h2>

                        <div class="grid grid-cols-1 gap-6 pt-6 md:grid-cols-2 md:gap-4">
                            
                            <div class="p-3 text-center border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10"
                                id="camera-box">

                                <!-- INITIAL / EMPTY STATE -->
                                <div id="camera-placeholder"
                                    class="flex flex-col items-center justify-center h-[230px] gap-2 py-4">

                                    <button type="button" id="openCameraBtn">
                                        <div
                                            class="flex items-center justify-center bg-gray-200 rounded-full hover:bg-gray-300 dark:bg-gray-600 w-14 h-14 dark:hover:bg-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                            </svg>
                                        </div>
                                    </button>
                                    <p class="px-4 py-2 text-sm text-gray-700 dark:text-white">
                                        Klik untuk membuka kamera
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Pastikan wajah Anda terlihat jelas dalam bingkai.
                                    </p>
                                </div>

                                <!-- CAMERA LIVE -->
                                <div id="camera-live" class="hidden">
                                    <video id="camera-video" class="w-full rounded-lg aspect-[4/3] bg-black" autoplay
                                        playsinline>
                                    </video>

                                    <button type="button" id="captureBtn" aria-label="Ambil Foto" class="my-4">
                                        <div
                                            class="flex items-center justify-center bg-gray-600 rounded-full w-14 h-14 hover:bg-gray-700">
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
                                    <img id="photo-preview" class="object-cover w-full rounded-lg" alt="Preview photo">
                                    <div class="flex justify-center gap-3 my-4">
                                        <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','id' => 'confirmBtn','ariaLabel' => 'Presensi sekarang','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','id' => 'confirmBtn','aria-label' => 'Presensi sekarang','color' => 'primary']); ?>
                                            <span>Gunakan</span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','id' => 'retakeBtn','color' => 'gray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','id' => 'retakeBtn','color' => 'gray']); ?> Ambil Ulang
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

                                <!-- HIDDEN CANVAS -->
                                <canvas id="camera-canvas" class="hidden"></canvas>

                                <!-- OUTPUT -->
                                <input type="hidden" name="face_snapshot" id="faceSnapshot">
                            </div>

                            <div>
                                <div class="grid gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label for="note" class="mb-2 text-sm text-gray-500 dark:text-gray-400">Catatan
                                            (opsional)</label>
                                        <textarea id="note" name="note" rows="3"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10 focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                            placeholder="Tambahkan catatan singkat tentang shift Anda..."></textarea>
                                    </div>
                                    <div
                                        class="flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Waktu saat ini</p>
                                            <p class="text-sm font-medium" id="current-time-clockin"></p>
                                            <script> function updateTimeClockIn() { const now = new Date(); const hours = now.getHours().toString().padStart(2, '0'); const minutes = now.getMinutes().toString().padStart(2, '0'); const seconds = now.getSeconds().toString().padStart(2, '0'); const ampm = hours >= 12 ? 'PM' : 'AM'; document.getElementById('current-time-clockin').textContent = `${hours}:${minutes}:${seconds} ${ampm}`; } setInterval(updateTimeClockIn, 1000); updateTimeClockIn(); </script>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Jam Kerja</p>
                                            <p class="text-sm font-medium">
                                                <?php echo e(isset($employee->shift->start_time) ? \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i') : '-'); ?>

                                                -
                                                <?php echo e(isset($employee->shift->end_time) ? \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i') : '-'); ?>

                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => '/hr/attendance-history','color' => 'gray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => '/hr/attendance-history','color' => 'gray']); ?>Lihat Riwayat <?php echo $__env->renderComponent(); ?>
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
                        class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                        aria-labelledby="clock-out-title" onsubmit="return setLocationBeforeSubmit(event)">
                        <?php echo csrf_field(); ?>
                        <h2 id="clock-out-title" class="text-base font-medium">Sudah selesai kerja?</h2>

                        <div class="grid grid-cols-1 gap-6 pt-6 md:grid-cols-2 md:gap-4">
                            
                            <div class="p-3 text-center border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10"
                                id="camera-box-out">

                                <!-- INITIAL / EMPTY STATE -->
                                <div id="camera-placeholder-out"
                                    class="flex flex-col items-center justify-center h-[230px] gap-2 py-4">

                                    <button type="button" id="openCameraBtnOut">
                                        <div
                                            class="flex items-center justify-center bg-gray-200 rounded-full hover:bg-gray-300 dark:bg-gray-600 w-14 h-14 dark:hover:bg-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                            </svg>
                                        </div>
                                    </button>
                                    <p class="px-4 py-2 text-sm text-gray-700 dark:text-white">
                                        Klik untuk membuka kamera
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Pastikan wajah Anda terlihat jelas dalam bingkai.
                                    </p>
                                </div>

                                <!-- CAMERA LIVE -->
                                <div id="camera-live-out" class="hidden">
                                    <video id="camera-video-out" class="w-full rounded-lg aspect-[4/3] bg-black" autoplay
                                        playsinline>
                                    </video>

                                    <button type="button" id="captureBtnOut" aria-label="Ambil Foto" class="my-4">
                                        <div
                                            class="flex items-center justify-center bg-gray-600 rounded-full w-14 h-14 hover:bg-gray-700">
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
                                    <img id="photo-preview-out" class="object-cover w-full rounded-lg" alt="Preview photo">

                                    <div class="flex justify-center gap-3 my-4">
                                        <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','id' => 'confirmBtnOut','color' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','id' => 'confirmBtnOut','color' => 'danger']); ?>
                                            <span>Gunakan</span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'button','id' => 'retakeBtnOut','color' => 'gray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','id' => 'retakeBtnOut','color' => 'gray']); ?>
                                            Ambil Ulang
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

                                <!-- HIDDEN CANVAS -->
                                <canvas id="camera-canvas-out" class="hidden"></canvas>

                                <!-- OUTPUT -->
                                <input type="hidden" name="face_snapshot" id="faceSnapshotOut">
                            </div>

                            <div>
                                <div class="grid gap-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($attendanceToday): ?>
                                        <div
                                            class="flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10 h-[76px]">
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Jam masuk</p>
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
                                    <div
                                        class="flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg fflex bg-gray-50 dark:bg-gray-800/50 dark:border-white/10 h-[76px]">
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Waktu saat ini</p>
                                            <p class="text-sm font-medium" id="current-time-clockout"></p>
                                            <script> function updateTimeClockOut() { const now = new Date(); const hours = now.getHours().toString().padStart(2, '0'); const minutes = now.getMinutes().toString().padStart(2, '0'); const seconds = now.getSeconds().toString().padStart(2, '0'); const ampm = hours >= 12 ? 'PM' : 'AM'; document.getElementById('current-time-clockout').textContent = `${hours}:${minutes}:${seconds} ${ampm}`; } setInterval(updateTimeClockOut, 1000); updateTimeClockOut(); </script>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Jam Kerja</p>
                                            <p class="text-sm font-medium">
                                                <?php echo e(isset($employee->shift->start_time) ? \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i') : '-'); ?>

                                                -
                                                <?php echo e(isset($employee->shift->end_time) ? \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i') : '-'); ?>

                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['type' => 'submit','color' => 'danger','ariaLabel' => 'Presensi keluar sekarang','id' => 'presensiKeluarButton']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','color' => 'danger','aria-label' => 'Presensi keluar sekarang','id' => 'presensiKeluarButton']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['tag' => 'a','href' => '/hr/attendance-history','color' => 'gray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tag' => 'a','href' => '/hr/attendance-history','color' => 'gray']); ?> Lihat Riwayat
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
                    <div
                        class="flex flex-col items-center justify-center p-8 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <svg class="w-16 h-16 mb-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                        </svg>
                        <h3 class="mb-2 text-xl font-bold text-blue-600 dark:text-blue-400">Presensi Selesai 🎉</h3>
                        <p class="mb-4 text-center text-gray-600 dark:text-gray-400">Kamu sudah menyelesaikan presensi hari
                            ini. Terima kasih atas kerja kerasmu!</p>
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

            <div class="md:col-span-3">
                <div
                    class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-medium">Lokasi</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Verifikasi lokasi Anda sebelum presensi
                                masuk.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Akurasi</span>
                            <span
                                class="px-2 py-1 text-xs font-medium text-gray-500 border border-gray-200 rounded-md bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">~30m</span>
                        </div>
                    </div>

                    <div class="overflow-hidden border rounded-lg border-slate-200">
                        <div id="map" class="relative h-[400px] w-full bg-slate-100">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-4 text-sm sm:grid-cols-3" id="location-info">
                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <p class="text-gray-500 dark:text-gray-400">Alamat</p>
                            <p class="font-medium" id="address-text">-</p>
                        </div>
                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <p class="text-gray-500 dark:text-gray-400">Koordinat</p>
                            <p class="font-medium" id="coords-text">-</p>
                        </div>
                        <div
                            class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                            <p class="text-gray-500 dark:text-gray-400">Status</p>
                            <p class="font-medium" id="status-text">-</p>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // LEAFLET MAP INITIALIZATION
        const map = L.map('map').setView([<?php echo e($employee->office->latitude); ?>, <?php echo e($employee->office->longitude); ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        setTimeout(() => {
            map.invalidateSize();
        }, 500);

        const officeCenter = [<?php echo e($employee->office->latitude); ?>, <?php echo e($employee->office->longitude); ?>];
        const officeRadius = <?php echo e($office->radius_meters); ?>;
        let marker; // Marker for user's current location

        const circle = L.circle(officeCenter, {
            color: '#2563eb',
            weight: 1,
            fillColor: '#3b82f6',
            fillOpacity: 0.2,
            radius: officeRadius
        }).addTo(map);

        const addressText = document.getElementById('address-text');
        const coordsText = document.getElementById('coords-text');
        const statusText = document.getElementById('status-text');

        function isWithinRadius(userLat, userLng, center, radius) {
            const distance = map.distance([userLat, userLng], center);
            return distance <= radius;
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

        // UX OPTIMIZATION JAVASCRIPT LOGIC
        let isLocationReadyForSubmission = false; // Flag to indicate if location is successfully obtained for clock-in

        // Clock-in elements
        const presensiMasukForm = document.querySelector('form[action="<?php echo e(route('attendance.clockin')); ?>"]');
        const presensiMasukButton = document.getElementById('presensiMasukButton');
        const latInputClockIn = document.getElementById('lat');
        const lngInputClockIn = document.getElementById('lng');
        const mapContainer = document.getElementById('map'); // Target to scroll to

        // Clock-out elements
        const presensiKeluarForm = document.querySelector('form[action="<?php echo e(route('attendance.clockout')); ?>"]');
        const latInputClockOut = document.getElementById('lat-out');
        const lngInputClockOut = document.getElementById('lng-out');

        // Helper to update button content and state (for clock-in button)
        function updatePresensiMasukButtonState(text, isLoading = false, type = 'button') {
            if (!presensiMasukButton) return;

            presensiMasukButton.disabled = isLoading;
            presensiMasukButton.type = type;

            const span = presensiMasukButton.querySelector('span');
            if (span) {
                span.textContent = text;
            }

            let spinner = presensiMasukButton.querySelector('.button-spinner');
            if (isLoading) {
                if (!spinner) {
                    spinner = document.createElement('span');
                    spinner.className = 'button-spinner ml-2';
                    spinner.innerHTML = '<svg class="w-5 h-5 text-current animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                    presensiMasukButton.appendChild(spinner);
                }
            } else {
                if (spinner) {
                    spinner.remove();
                }
            }
        }

        // Helper to update button content and state (for other buttons, e.g., clock-out)
        function updateGenericButtonState(button, text, isLoading = false) {
            if (!button) return;

            button.disabled = isLoading;

            const span = button.querySelector('span');
            if (span) {
                span.textContent = text;
            }

            let spinner = button.querySelector('.button-spinner');
            if (isLoading) {
                if (!spinner) {
                    spinner = document.createElement('span');
                    spinner.className = 'button-spinner ml-2';
                    spinner.innerHTML = '<svg class="w-5 h-5 text-current animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                    button.appendChild(spinner);
                }
            } else {
                if (spinner) {
                    spinner.remove();
                }
            }
        }

        // Function to handle "Tandai Lokasi" click for Clock-in
        async function handleTagLocationClick() {
            updatePresensiMasukButtonState('Mendapatkan Lokasi', true);

            // Scroll to map
            mapContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

            if (!navigator.geolocation) {
                alert('Geolocation tidak didukung browser ini.');
                updatePresensiMasukButtonState('Tandai Lokasi');
                isLocationReadyForSubmission = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lng]).addTo(map);
                map.setView([lat, lng], 15);

                coordsText.innerText = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                addressText.innerText = 'Memuat alamat...';
                const address = await getAddress(lat, lng);
                addressText.innerText = address;

                const inside = isWithinRadius(lat, lng, officeCenter, officeRadius);
                statusText.innerText = inside
                    ? '✅ Anda berada di dalam area kantor'
                    : '❌ Anda berada di luar area kantor';

                latInputClockIn.value = lat;
                lngInputClockIn.value = lng;

                isLocationReadyForSubmission = true;
                updatePresensiMasukButtonState('Presensi Masuk', false, 'submit'); // Change to submit type
                presensiMasukButton.onclick = null; // Remove this handler; form submission will now take over
            }, (error) => {
                console.error('Geolocation error:', error);
                alert('Gagal mendapatkan lokasi Anda. ' + error.message);
                updatePresensiMasukButtonState('Tandai Lokasi');
                isLocationReadyForSubmission = false;
            });
        }

        // Function to handle Clock-in form submission (after location is tagged)
        function handlePresensiMasukSubmission(event) {
            // VALIDASI FOTO
            if (!faceSnapshot.value) {
                event.preventDefault();
                alert('Silakan ambil foto presensi terlebih dahulu.');
                return;
            }

            // VALIDASI LOKASI (existing)
            if (!isLocationReadyForSubmission || latInputClockIn.value === '' || lngInputClockIn.value === '') {
                event.preventDefault();
                alert('Lokasi belum ditandai. Silakan klik "Tandai Lokasi" terlebih dahulu.');
                updatePresensiMasukButtonState('Tandai Lokasi');
                isLocationReadyForSubmission = false;
                presensiMasukButton.onclick = handleTagLocationClick;
                return;
            }

            // LOADING STATE
            updatePresensiMasukButtonState('Mengirim Presensi', true);
        }

        async function setLocationBeforeSubmit(event) {
            event.preventDefault();
            const form = event.target;
            // Find the submit button that triggered this event
            const submitButton = event.submitter || form.querySelector('button[type="submit"]');

            if (submitButton) {
                updateGenericButtonState(submitButton, 'Mengirim Presensi', true);
            }

            if (!navigator.geolocation) {
                alert('Geolocation tidak didukung browser ini.');
                if (submitButton) {
                    // Restore original text or default 'Submit' text
                    const originalText = submitButton.querySelector('span') ? submitButton.querySelector('span').textContent : 'Submit';
                    updateGenericButtonState(submitButton, originalText, false);
                }
                return false;
            }

            navigator.geolocation.getCurrentPosition(async (position) => {
                // Ensure correct lat/lng inputs for the specific form
                const targetLatInput = form.querySelector('#lat') || form.querySelector('#lat-out');
                const targetLngInput = form.querySelector('#lng') || form.querySelector('#lng-out');

                if (targetLatInput && targetLngInput) {
                    targetLatInput.value = position.coords.latitude;
                    targetLngInput.value = position.coords.longitude;
                }

                form.submit(); // Manually submit the form after getting location
            }, (error) => {
                console.error('Geolocation error:', error);
                alert('Gagal mendapatkan lokasi Anda. ' + error.message);
                if (submitButton) {
                    const originalText = submitButton.querySelector('span') ? submitButton.querySelector('span').textContent : 'Submit';
                    updateGenericButtonState(submitButton, originalText, false);
                }
            });
            return false; // Ensure default form submission is prevented
        }


        document.addEventListener('DOMContentLoaded', () => {
            // Initialize Clock-in button logic
            if (presensiMasukButton && presensiMasukForm) {
                updatePresensiMasukButtonState('Tandai Lokasi'); // Set initial text
                presensiMasukButton.onclick = handleTagLocationClick; // Attach initial action
                presensiMasukForm.addEventListener('submit', handlePresensiMasukSubmission); // Attach final submission handler
            }
        });

        // CAMERA SETUP FOR CLOCK-IN
        if (document.getElementById('camera-box')) {

            const openCameraBtn = document.getElementById('openCameraBtn');
            const captureBtn = document.getElementById('captureBtn');
            const retakeBtn = document.getElementById('retakeBtn');
            const confirmBtn = document.getElementById('confirmBtn');

            const placeholder = document.getElementById('camera-placeholder');
            const cameraLive = document.getElementById('camera-live');
            const cameraPreview = document.getElementById('camera-preview');

            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            const photoPreview = document.getElementById('photo-preview');
            const faceSnapshot = document.getElementById('faceSnapshot');

            let stream = null;

            async function openCamera() {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' }
                });

                video.srcObject = stream;

                placeholder.classList.add('hidden');
                cameraLive.classList.remove('hidden');
            }

            function capturePhoto() {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0);

                const imageData = canvas.toDataURL('image/jpeg');

                photoPreview.src = imageData;
                faceSnapshot.value = imageData;

                stopCamera();

                cameraLive.classList.add('hidden');
                cameraPreview.classList.remove('hidden');
            }

            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
            }

            function retakePhoto() {
                cameraPreview.classList.add('hidden');
                placeholder.classList.remove('hidden');
                faceSnapshot.value = '';
            }

            openCameraBtn.addEventListener('click', openCamera);
            captureBtn.addEventListener('click', capturePhoto);
            retakeBtn.addEventListener('click', retakePhoto);

            confirmBtn.addEventListener('click', () => {
                alert('Foto siap digunakan');

                // OPTIONAL UX IMPROVEMENT
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Foto Siap';
            });

            presensiMasukButton.addEventListener('click', () => {
                if (!faceSnapshot.value) {
                    alert('Silakan ambil foto terlebih dahulu.');
                    return;
                }
            });
        }

        // CAMERA SETUP FOR CLOCK-OUT
        if (document.getElementById('camera-box-out')) {

            const openCameraBtnOut = document.getElementById('openCameraBtnOut');
            const captureBtnOut = document.getElementById('captureBtnOut');
            const retakeBtnOut = document.getElementById('retakeBtnOut');
            const confirmBtnOut = document.getElementById('confirmBtnOut');

            const placeholderOut = document.getElementById('camera-placeholder-out');
            const cameraLiveOut = document.getElementById('camera-live-out');
            const cameraPreviewOut = document.getElementById('camera-preview-out');

            const videoOut = document.getElementById('camera-video-out');
            const canvasOut = document.getElementById('camera-canvas-out');
            const photoPreviewOut = document.getElementById('photo-preview-out');
            const faceSnapshotOut = document.getElementById('faceSnapshotOut');

            let streamOut = null;

            async function openCameraOut() {
                streamOut = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' }
                });

                videoOut.srcObject = streamOut;

                placeholderOut.classList.add('hidden');
                cameraLiveOut.classList.remove('hidden');
            }

            function capturePhotoOut() {
                canvasOut.width = videoOut.videoWidth;
                canvasOut.height = videoOut.videoHeight;

                const ctx = canvasOut.getContext('2d');
                ctx.drawImage(videoOut, 0, 0);

                const imageData = canvasOut.toDataURL('image/jpeg');

                photoPreviewOut.src = imageData;
                faceSnapshotOut.value = imageData;

                stopCameraOut();

                cameraLiveOut.classList.add('hidden');
                cameraPreviewOut.classList.remove('hidden');
            }

            function stopCameraOut() {
                if (streamOut) {
                    streamOut.getTracks().forEach(track => track.stop());
                    streamOut = null;
                }
            }

            function retakePhotoOut() {
                cameraPreviewOut.classList.add('hidden');
                placeholderOut.classList.remove('hidden');
                faceSnapshotOut.value = '';
            }

            openCameraBtnOut?.addEventListener('click', openCameraOut);
            captureBtnOut?.addEventListener('click', capturePhotoOut);
            retakeBtnOut?.addEventListener('click', retakePhotoOut);

            confirmBtnOut?.addEventListener('click', () => {
                alert('Foto presensi keluar siap digunakan');
                confirmBtnOut.disabled = true;
                confirmBtnOut.textContent = 'Foto Siap';
            });

            presensiKeluarButton?.addEventListener('click', (e) => {
                if (!faceSnapshotOut.value) {
                    e.preventDefault();
                    alert('Silakan ambil foto terlebih dahulu.');
                    return;
                }
            });
        }
    </script>

    <?php $__env->startPush('scripts'); ?>
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

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
<?php endif; ?><?php /**PATH C:\laragon\www\erp-app\resources\views\filament\pages\hr\attendance.blade.php ENDPATH**/ ?>