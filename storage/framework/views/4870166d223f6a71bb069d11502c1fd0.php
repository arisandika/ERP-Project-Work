<div style="width: 100%;">
    <?php
        $record = $getRecord();
        $hasIn = $record->latitude_in && $record->longitude_in;
        $hasOut = $record->latitude_out && $record->longitude_out;

        $officeData = $record->employee?->office;
        $officeLat = (float) ($officeData?->latitude ?? 0);
        $officeLng = (float) ($officeData?->longitude ?? 0);
        $officeRadius = (int) ($officeData?->radius_meters ?? 100);

        $photoIn = $record->face_snapshot_in ? asset('storage/' . $record->face_snapshot_in) : null;
        $photoOut = $record->face_snapshot_out ? asset('storage/' . $record->face_snapshot_out) : null;

        $timeIn = $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '-';
        $timeOut = $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '-';

        $mapData = [
            'hasIn' => (bool) $hasIn,
            'hasOut' => (bool) $hasOut,
            'office' => [$officeLat, $officeLng],
            'radius' => $officeRadius,
            'inCoords' => [(float) $record->latitude_in, (float) $record->longitude_in],
            'outCoords' => [(float) $record->latitude_out, (float) $record->longitude_out],
            'inTime' => $timeIn,
            'outTime' => $timeOut,
            'inPhoto' => $photoIn,
            'outPhoto' => $photoOut,
        ];
    ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$hasIn && !$hasOut): ?>
        <div
            class="flex items-center justify-center h-40 text-sm text-gray-400 border rounded-xl border-slate-200 dark:border-slate-700">
            Tidak ada data lokasi presensi.
        </div>
    <?php else: ?>
            <div class="custom-map-wrap" style="width: 100%; position: relative; z-index: 1;" x-data="{
                                                map: null,
                                                data: <?php echo \Illuminate\Support\Js::from($mapData)->toHtml() ?>,
                                                initMap() {
                                                    if (!document.getElementById('leaflet-css')) {
                                                        let link = document.createElement('link');
                                                        link.id = 'leaflet-css';
                                                        link.rel = 'stylesheet';
                                                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                                                        document.head.appendChild(link);
                                                    }

                                                    if (!document.getElementById('leaflet-custom-style')) {
                                                        let style = document.createElement('style');
                                                        style.id = 'leaflet-custom-style';
                                                        style.innerHTML = `
                                                            .custom-map-wrap .leaflet-popup-content-wrapper { border-radius: 12px; overflow: hidden; }
                                                            .custom-map-wrap .leaflet-popup-content { margin: 0; width: 220px !important; }
                                                            .popup-header { background: #f8fafc; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-weight: bold; }
                                                            .popup-body { padding: 10px 14px; }
                                                            .popup-img { width: 100%; height: 140px; object-fit: cover; border-radius: 8px; margin-top: 8px; border: 1px solid #e2e8f0; }
                                                        `;
                                                        document.head.appendChild(style);
                                                    }

                                                    if (typeof L === 'undefined') {
                                                        let script = document.createElement('script');
                                                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                                                        script.onload = () => this.buildMap();
                                                        document.head.appendChild(script);
                                                    } else {
                                                        this.buildMap();
                                                    }
                                                },
                                                buildMap() {
                                                    let el = this.$refs.mapElement;
                                                    let center = this.data.hasIn ? this.data.inCoords : this.data.office;

                                                    this.map = L.map(el).setView(center, 16);

                                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                                        maxZoom: 14,
                                                        attribution: '© OpenStreetMap'
                                                    }).addTo(this.map);

                                                    // Marker Kantor & Radius
                                                    L.circle(this.data.office, {
                                                        color: '#2563eb', weight: 2,
                                                        fillColor: '#3b82f6', fillOpacity: 0.15,
                                                        radius: this.data.radius
                                                    }).addTo(this.map);

                                                    L.marker(this.data.office, {
                                                        icon: L.divIcon({
                                                            html: `<div style='width:30px;height:30px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);'><svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='white' width='16' height='16'><path stroke-linecap='round' stroke-linejoin='round' d='M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21' /></svg></div>`,
                                                            className: '', iconSize: [30, 30], iconAnchor: [15, 30]
                                                        })
                                                    }).addTo(this.map).bindPopup(`<div class='popup-body'><b>Radius Kantor</b></div>`);

                                                    const createMarker = (coords, type, color, time, photo) => {
                                                        let label = type === 'in' ? 'IN' : 'OUT';
                                                        let title = type === 'in' ? 'Presensi Masuk' : 'Presensi Keluar';
                                                        let photoHtml = photo ? `<img src='${photo}' class='popup-img' alt='Foto'>` : `<div style='margin-top:10px; color:#94a3b8; font-size:12px;'>Foto tidak tersedia</div>`;

                                                        let popupHtml = `
                                                            <div class='popup-header'>${title}</div>
                                                            <div class='popup-body'>
                                                                <div><b>Waktu:</b> ${time}</div>
                                                                ${photoHtml}
                                                            </div>
                                                        `;

                                                        L.marker(coords, {
                                                            icon: L.divIcon({
                                                                html: `<div style='width:35px;height:35px;background:${color};border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.4);border:2px solid white;'><span style='color:white;font-weight:bold;font-size:10px;'>${label}</span></div>`,
                                                                className: '', iconSize: [35, 35], iconAnchor: [17, 35]
                                                            })
                                                        }).addTo(this.map).bindPopup(popupHtml);
                                                    };

                                                    // Marker Absen Masuk & Keluar
                                                    if (this.data.hasIn) createMarker(this.data.inCoords, 'in', '#10b981', this.data.inTime, this.data.inPhoto);
                                                    if (this.data.hasOut) createMarker(this.data.outCoords, 'out', '#f43f5e', this.data.outTime, this.data.outPhoto);

                                                    // FitBounds (Menyesuaikan zoom agar semua titik terlihat)
                                                    let bounds = [this.data.office];
                                                    if (this.data.hasIn) bounds.push(this.data.inCoords);
                                                    if (this.data.hasOut) bounds.push(this.data.outCoords);

                                                    if(bounds.length > 1) {
                                                        this.map.fitBounds(L.latLngBounds(bounds), { padding: [40, 40] });
                                                    }

                                                    // Objek Observer agar map tidak blank
                                                    const resizeObserver = new ResizeObserver(() => {
                                                        this.map.invalidateSize();
                                                    });
                                                    resizeObserver.observe(el);
                                                }
                                            }" x-init="initMap()">
                
                <div x-ref="mapElement"
                    style="width: 100%; height: 450px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f1f5f9; z-index: 10;">
                </div>

                
                <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:12px; font-size:13px; color:#6b7280;">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasIn): ?>
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span style="width:12px;height:12px;border-radius:50%;background:#10b981;"></span> Masuk (<?php echo e($timeIn); ?>)
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasOut): ?>
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span style="width:12px;height:12px;border-radius:50%;background:#f43f5e;"></span> Keluar
                            (<?php echo e($timeOut); ?>)
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <span style="display:flex; align-items:center; gap:6px;">
                        <span
                            style="width:12px;height:12px;border-radius:50%;background:#3b82f6;opacity:0.7;border:1px solid #2563eb;"></span>
                        Radius Kantor
                    </span>
                </div>
            </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH /var/www/erp-app-main/resources/views/filament/infolists/components/attendance-map.blade.php ENDPATH**/ ?>