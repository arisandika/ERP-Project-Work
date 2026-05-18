<div style="width: 100%;">
    <?php
        $assignment = $getRecord();
        $records = $assignment->visitRecords()->with('photos')->get();

        $mapPoints = $records
            ->filter(fn($r) => $r->hasCoordinates())
            ->map(fn($r) => [
                'order'        => $r->visit_order,
                'lat'          => (float) $r->latitude,
                'lng'          => (float) $r->longitude,
                'address'      => $r->location_address ?? '-',
                'visitedAt'    => $r->visited_at?->format('d M Y H:i') ?? '-',
                'result_key'   => $r->visit_result,
                'result_label' => \App\Models\SalesActivity\VisitRecord::resultOptions()[$r->visit_result] ?? $r->visit_result,
                'description'  => $r->description,
                'photos'       => $r->photos->map(fn($p) => [
                    'url'     => $p->url(),
                    'caption' => $p->caption ?? 'Foto Kunjungan',
                ])->values()->toArray(),
            ])
            ->values()
            ->toArray();
    ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($mapPoints)): ?>
        <div class="flex flex-col items-center justify-center h-48 border border-dashed rounded-2xl border-slate-200 bg-slate-50 dark:bg-gray-900 dark:border-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
            <p class="text-sm text-gray-500">Belum ada rekaman koordinat untuk tugas ini.</p>
        </div>
    <?php else: ?>
        <div class="custom-visit-map-wrap" style="width: 100%; position: relative; z-index: 1;"
             x-data="{
                map: null,
                points: <?php echo \Illuminate\Support\Js::from($mapPoints)->toHtml() ?>,
                initMap() {
                    if (!document.getElementById('leaflet-css')) {
                        let link = document.createElement('link');
                        link.id = 'leaflet-css';
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        document.head.appendChild(link);
                    }
                    if (!document.getElementById('visit-leaflet-custom-style')) {
                        let style = document.createElement('style');
                        style.id = 'visit-leaflet-custom-style';
                        style.innerHTML = '.custom-visit-map-wrap .leaflet-popup-content-wrapper{border-radius:12px;overflow:hidden;}.custom-visit-map-wrap .leaflet-popup-content{margin:0;width:240px!important;font-family:inherit;}.custom-visit-map-wrap .leaflet-marker-icon:focus{outline:none;}.custom-visit-map-wrap .leaflet-popup-tip-container{display:none;}.vm-popup-header{background:#f8fafc;padding:10px 14px;border-bottom:1px solid #e2e8f0;font-weight:bold;font-size:13px;}.vm-popup-body{padding:10px 14px;font-size:12px;}.vm-popup-row{margin-bottom:4px;color:#4b5563;}.vm-popup-desc{background:#f3f4f6;padding:6px;border-radius:6px;margin-top:6px;font-size:11px;}.vm-photos-wrap{display:flex;gap:6px;overflow-x:auto;padding-top:8px;padding-bottom:2px;}.vm-photo-img{width:68px;height:68px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;flex-shrink:0;}.vm-photo-img:hover{opacity:0.85;}';
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
                openPreview(url, caption) {
                    let existing = document.getElementById('vm-img-preview-overlay');
                    if (existing) existing.remove();
                    let overlay = document.createElement('div');
                    overlay.id = 'vm-img-preview-overlay';
                    overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.85);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;cursor:zoom-out;';
                    let img = document.createElement('img');
                    img.src = url;
                    img.style.cssText = 'max-width:100%;max-height:85vh;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,0.5);object-fit:contain;';
                    let cap = document.createElement('div');
                    cap.textContent = caption;
                    cap.style.cssText = 'margin-top:12px;color:#fff;font-size:13px;opacity:0.8;';
                    overlay.appendChild(img);
                    overlay.appendChild(cap);
                    overlay.addEventListener('click', () => overlay.remove());
                    document.body.appendChild(overlay);
                },
                buildMap() {
                    if (this.map) return;
                    let el = this.$refs.mapElement;
                    this.map = L.map(el, { zoomControl: false });
                    L.control.zoom({ position: 'bottomright' }).addTo(this.map);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 13,
                        attribution: '© OpenStreetMap'
                    }).addTo(this.map);

                    const latLngs = [];
                    const self = this;

                    this.points.forEach(p => {
                        const pos = [p.lat, p.lng];
                        latLngs.push(pos);

                        let markerColor = '#3B82F6';
                        if (p.result_key === 'interested' || p.result_key === 'deal_progressed') markerColor = '#10B981';
                        if (p.result_key === 'not_interested' || p.result_key === 'failed') markerColor = '#EF4444';

                        const icon = L.divIcon({
                            className: '',
                            html: '<div style=\'width:32px;height:32px;border-radius:50%;background:' + markerColor + ';border:3px solid #fff;box-shadow:0 3px 6px rgba(0,0,0,0.3);color:#fff;font-weight:bold;display:flex;align-items:center;justify-content:center;font-size:13px;\'>' + p.order + '</div>',
                            iconSize: [32, 32],
                            iconAnchor: [16, 16],
                        });

                        let photosHtml = '';
                        if (p.photos && p.photos.length > 0) {
                            photosHtml += '<div class=\'vm-photos-wrap\'>';
                            p.photos.forEach(f => {
                                photosHtml += '<img src=\'' + f.url + '\' class=\'vm-photo-img\' title=\'' + f.caption + '\' data-url=\'' + f.url + '\' data-caption=\'' + f.caption + '\'>';
                            });
                            photosHtml += '</div>';
                        }

                        const popupHtml = '<div class=\'vm-popup-header\'>Kunjungan ke-' + p.order + ' &mdash; ' + p.result_label + '</div>'
                            + '<div class=\'vm-popup-body\'>'
                            + '<div class=\'vm-popup-row\'><b>Waktu:</b> ' + p.visitedAt + '</div>'
                            + '<div class=\'vm-popup-row\'><b>Lokasi:</b> ' + p.address + '</div>'
                            + (p.description ? '<div class=\'vm-popup-desc\'>' + p.description + '</div>' : '')
                            + photosHtml
                            + '</div>';

                        const marker = L.marker(pos, { icon }).addTo(this.map).bindPopup(popupHtml);

                        marker.on('popupopen', function() {
                            document.querySelectorAll('.vm-photo-img').forEach(img => {
                                img.addEventListener('click', function() {
                                    self.openPreview(this.dataset.url, this.dataset.caption);
                                });
                            });
                        });
                    });

                    if (latLngs.length > 1) {
                        let lineColor = '#6366F1';
                        const last = this.points[this.points.length - 1];
                        if (last.result_key === 'interested' || last.result_key === 'deal_progressed') lineColor = '#10B981';
                        else if (last.result_key === 'not_interested' || last.result_key === 'failed') lineColor = '#EF4444';
                        L.polyline(latLngs, { color: lineColor, weight: 3, opacity: 0.6, dashArray: '5, 10' }).addTo(this.map);
                    }

                    this.map.fitBounds(L.latLngBounds(latLngs), { padding: [40, 40] });
                    new ResizeObserver(() => { this.map.invalidateSize(); }).observe(el);
                }
             }"
             x-init="initMap()">

            <div x-ref="mapElement" style="width: 100%; height: 480px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f1f5f9; z-index: 10;"></div>

            <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:12px; font-size:13px; color:#6b7280; align-items:center;">
                <span style="font-weight:600; color:#4b5563;">Keterangan Marker:</span>
                <span style="display:flex; align-items:center; gap:6px;"><span style="width:12px;height:12px;border-radius:50%;background:#3B82F6;display:inline-block;"></span> Umum</span>
                <span style="display:flex; align-items:center; gap:6px;"><span style="width:12px;height:12px;border-radius:50%;background:#10B981;display:inline-block;"></span> Prospek / Lanjut</span>
                <span style="display:flex; align-items:center; gap:6px;"><span style="width:12px;height:12px;border-radius:50%;background:#EF4444;display:inline-block;"></span> Gagal / Ditolak</span>
                <span style="display:flex; align-items:center; gap:6px; margin-left:auto; font-style:italic; color:#9ca3af;">
                    Garis putus-putus menunjukkan rute perjalanan
                </span>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\laragon\www\erp-app-main\resources\views/filament/infolists/components/visit-map.blade.php ENDPATH**/ ?>