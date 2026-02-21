<?php $__env->startPush('head'); ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-sA+4E1D7R7syld7ZCkHfZZx4nU+v+8g1z2/3LrALPqo=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-o9N1j0NfL3J4eU3yZ3QfdrY4mytD3InYt8E8Z3T9O4A=" crossorigin=""></script>
<?php $__env->stopPush(); ?>

<?php if (isset($component)) { $__componentOriginalb525200bfa976483b4eaa0b7685c6e24 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widget','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => ['heading' => 'Tracking Presensi Karyawan']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['heading' => 'Tracking Presensi Karyawan']); ?>
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="grid gap-y-2">
                <label for="filter-date" class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    Tanggal <sup class="text-danger-600 dark:text-danger-400">*</sup>
                </label>
                <div
                    class="flex bg-white rounded-lg shadow-sm fi-input-wrp ring-1 ring-gray-950/10 dark:ring-white/20 dark:bg-white/5 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                    <div class="flex items-center border-gray-200 ps-3 pe-3 border-e dark:border-white/10">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-calendar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-400 dark:text-gray-500']); ?>
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
                    <input id="filter-date" type="date" value="<?php echo e(request('date', now()->toDateString())); ?>"
                        class="w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white" />
                </div>
            </div>

            <div class="grid gap-y-2">
                <label for="filter-office" class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    Kantor Cabang <sup class="text-danger-600 dark:text-danger-400">*</sup>
                </label>
                <div
                    class="flex bg-white rounded-lg shadow-sm fi-input-wrp ring-1 ring-gray-950/10 dark:ring-white/20 dark:bg-white/5 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500">
                    <div class="flex items-center border-gray-200 ps-3 pe-3 border-e dark:border-white/10">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-building-office'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-5 h-5 text-gray-400 dark:text-gray-500']); ?>
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
                    <select id="filter-office"
                        class="w-full border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none dark:text-white">
                        <option value="">Semua Kantor</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = \App\Models\HR\Office::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $office): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($office->id); ?>"><?php echo e($office->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <div id="map-container" class="relative overflow-hidden rounded-lg" data-navigate-once wire:ignore>
            <div id="map" class="relative h-[450px] w-full rounded-lg z-[1]"></div>

            <div id="map-loading-overlay"
                class="absolute inset-0 bg-white/70 dark:bg-gray-900/60 backdrop-blur-sm flex items-center justify-center hidden z-[1000] transition-all">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-10 h-10 border-4 rounded-full border-primary-500 border-t-transparent animate-spin">
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Memuat data peta...</span>
                </div>
            </div>
        </div>

        <div id="image-preview-modal"
            class="fixed inset-0 z-[2000] hidden items-center justify-center bg-black/70 backdrop-blur-sm">

            <div class="relative w-full max-w-3xl mx-4">
                <button id="close-image-modal"
                    class="absolute flex items-center justify-center w-8 h-8 text-gray-800 bg-white rounded-full shadow -top-3 -right-3">
                    ✕
                </button>

                <img id="image-preview-content" src=""
                    class="w-full max-h-[80vh] object-contain rounded-lg shadow-xl bg-white" />
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
<?php if (isset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $attributes = $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $component = $__componentOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        let map = null;
        let markersLayer = null;

        document.addEventListener("livewire:initialized", initAttendanceMap);
        document.addEventListener("livewire:navigated", initAttendanceMap);

        async function initAttendanceMap() {
            const mapEl = document.getElementById("map");
            if (!mapEl) return;

            if (map) {
                map.remove();
                map = null;
            }

            map = L.map("map").setView([-6.2, 106.816666], 15);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "&copy; OpenStreetMap contributors"
            }).addTo(map);

            markersLayer = L.layerGroup().addTo(map);

            await loadAttendanceData();
        }

        async function loadAttendanceData() {
            const overlay = document.getElementById('map-loading-overlay');
            overlay.classList.remove('hidden');

            try {
                const date = document.getElementById('filter-date').value;
                const office = document.getElementById('filter-office').value;

                const url = `<?php echo e(route('api.hr.attendance.map-data')); ?>?date=${date}&office_id=${office}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    renderMarkers(result.data);
                }
            } catch (error) {
                console.error("❌ Gagal memuat data:", error);
            } finally {
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }

        function renderMarkers(attendances) {
            markersLayer.clearLayers();

            attendances.forEach(emp => {
                if (!emp.lat || !emp.lng) return;

                const icon = L.divIcon({
                    html: `
                                    <div style="width: 35px; height: 35px;">
                                        <img src="${emp.photo}" alt="${emp.name}"
                                            style="width: 35px; height: 35px; border-radius: 50%; border: 2px solid #3b82f6; object-fit: cover;" />
                                    </div>`,
                    className: "",
                    iconSize: [45, 45],
                    iconAnchor: [22, 45],
                });

                const marker = L.marker([emp.lat, emp.lng], { icon }).addTo(markersLayer);
                marker.bindPopup(`
                                <div class="text-sm text-gray-800" style="width: 301px !important;">
                                    <div class="flex items-center gap-3 mb-2">
                                        <img src="${emp.photo}" alt="${emp.name}"
                                            style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid #3b82f6; object-fit: cover;" />
                                        <div>
                                            <strong>${emp.name}</strong><br>
                                            <span class="text-xs text-gray-500">${emp.position}</span><br>
                                            <span class="text-xs text-gray-500">${emp.department}</span>
                                        </div>
                                    </div>
                                    <hr class="my-1 border-gray-200" />
                                    <div class="grid grid-cols-2 gap-4 mt-3">
                                        <span><b>Status:</b> ${emp.status}</span>
                                        <span><b>Masuk:</b> ${emp.clock_in}</span>
                                        <span><b>Keluar:</b> ${emp.clock_out}</span>
                                        <span><b>Kantor:</b> ${emp.office.name}</span>
                                        <span><b>Mode:</b> ${emp.can_wfa ? 'Kerja di Rumah' : 'Kerja di Kantor'}</span>
                                        <span><b>Shift:</b> ${emp.shift.name} (${emp.shift.start_time} - ${emp.shift.end_time})</span>
                                        <div>
                                            <b>Foto Masuk:</b><br>
                                            ${emp.face_snapshot_in ? `<img src="${emp.face_snapshot_in}"
                                                alt="Face In"
                                                class="mt-2 transition cursor-pointer attendance-image hover:opacity-80"
                                                style="width: 100%; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb;"
                                                data-src="${emp.face_snapshot_in}" />` : '<span class="text-xs text-gray-400">Tidak tersedia</span>'}
                                        </div>
                                        <div>
                                            <b>Foto Pulang:</b><br>
                                                ${emp.face_snapshot_out ? `<img src="${emp.face_snapshot_out}"
                                                alt="Face Out"
                                                class="mt-2 transition cursor-pointer attendance-image hover:opacity-80"
                                                style="width: 100%; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb;"
                                                data-src="${emp.face_snapshot_out}" />` : '<span class="text-xs text-gray-400">Tidak tersedia</span>'}
                                        </div>
                                    </div>
                                </div>
                            `);

                if (emp.office.lat && emp.office.lng && emp.office.radius) {
                    L.circle([emp.office.lat, emp.office.lng], {
                        color: '#2563eb',
                        weight: 1,
                        fillColor: '#3b82f6',
                        fillOpacity: 0.2,
                        radius: emp.office.radius,
                    }).addTo(markersLayer);
                }
            });

            if (attendances.length) {
                const bounds = L.latLngBounds(attendances.map(a => [a.lat, a.lng]));
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }

        document.getElementById('filter-date').addEventListener('change', loadAttendanceData);
        document.getElementById('filter-office').addEventListener('change', loadAttendanceData);

        document.addEventListener('click', function (e) {
            const img = e.target.closest('.attendance-image');
            if (!img) return;

            const modal = document.getElementById('image-preview-modal');
            const modalImg = document.getElementById('image-preview-content');

            modalImg.src = img.dataset.src;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        document.getElementById('close-image-modal')?.addEventListener('click', closeImageModal);
        document.getElementById('image-preview-modal')?.addEventListener('click', function (e) {
            if (e.target.id === 'image-preview-modal') {
                closeImageModal();
            }
        });

        function closeImageModal() {
            const modal = document.getElementById('image-preview-modal');
            const modalImg = document.getElementById('image-preview-content');

            modalImg.src = '';
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
<?php $__env->stopPush(); ?><?php /**PATH C:\laragon\www\erp-app\resources\views/filament/widgets/hr/attendance-map-overview.blade.php ENDPATH**/ ?>