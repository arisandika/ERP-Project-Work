<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <style>
            .button-spinner { display: inline-block; vertical-align: middle; }
            .button-spinner svg { animation: spin 1s linear infinite; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .leaflet-popup-content-wrapper { border-radius: 12px; }
            .leaflet-popup-content { margin: 12px; }
            .timer-badge {
                animation: pulse 2s cubic-bezier(1, 0, 0, 1) infinite;
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.6; }
            }
        </style>
    @endpush

    {{-- ROOT ELEMENT UNTUK LIVEWIRE --}}
    <div class="w-full" wire:ignore.self>
        <main class="max-w-7xl">

            {{-- TIMER JIKA SUDAH CHECK-IN --}}
            @if($this->isCheckOutMode())
                <div class="mb-6">
                    <div class="flex items-center gap-4 p-4 text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl shadow-lg">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-full bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5v2.25h-9V12h3v2.25h1.5V10.5H7.5V6A5.25 5.25 0 0112 1.5a5.25 5.25 0 0 1 5.25 5.25v1.5m-6.75 6v3.75m6.75-3.75v-3.75M9 18h6" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm opacity-90">Sedang dalam kunjungan</p>
                            <p class="text-2xl font-bold">Check-in: {{ \Carbon\Carbon::parse($this->visitRecord->check_in_at)->format('H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <div id="duration-timer" class="text-2xl font-bold timer-badge">--:--</div>
                            <p class="text-xs opacity-80">durasi</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- FORM --}}
            <form wire:submit.prevent="checkOut" id="visit-form">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-12">

                    {{-- KOLOM KIRI (7 Kolom): KAMERA & FORM INPUT --}}
                    <div class="space-y-6 md:col-span-7">

                        {{-- 0. TUJUAN KUNJUNGAN (check-in mode) --}}
                        @if($this->isCheckInMode())
                            <div class="p-6 fi-section rounded-2xl ring-1">
                                <h2 class="mb-4 text-base font-medium">Tujuan Kunjungan</h2>

                                <div class="space-y-5">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-semibold">Pilih Tujuan<span class="text-red-500">*</span></label>
                                        <select wire:model="visit_purpose" class="w-full px-3 py-3 text-sm border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                                            <option value="">— Pilih tujuan kunjungan —</option>
                                            @foreach($this->getPurposeOptions() as $key => $label)
                                                <option value="{{ $key }}" {{ ($this->visit_purpose == $key) ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('visit_purpose')
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- 1. BAGIAN KAMERA --}}
                        <div class="p-6 fi-section rounded-2xl ring-1">
                            <h2 class="mb-4 text-base font-medium">Pengambilan Bukti Foto <span class="text-xs text-gray-500">(Min. 1)</span></h2>

                            <div class="grid gap-6 md:gap-4">
                                <div class="p-4 text-center border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark" id="camera-box">

                                    <!-- INITIAL / EMPTY STATE -->
                                    <div id="camera-placeholder" class="flex flex-col items-center justify-center h-[280px] gap-2 py-4">
                                        <button type="button" id="openCameraBtn">
                                            <div class="flex items-center justify-center rounded-full bg-secondary-light hover:bg-accent-light dark:bg-secondary-dark w-14 h-14 dark:hover:bg-main-dark ring-1 ring-border-light dark:ring-border-dark">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                                </svg>
                                            </div>
                                        </button>
                                        <p class="px-4 pt-2 pb-1 text-sm font-semibold">Klik untuk membuka kamera</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Gunakan mode landscape/portrait untuk mengambil foto.</p>
                                    </div>

                                    <!-- CAMERA LIVE -->
                                    <div id="camera-live" class="hidden">
                                        <video id="camera-video" class="w-full rounded-2xl aspect-[4/3] bg-black object-cover" autoplay playsinline></video>
                                        <button type="button" id="captureBtn" aria-label="Ambil Foto" class="mt-4">
                                            <div class="flex items-center justify-center rounded-full w-14 h-14 bg-secondary-light hover:bg-accent-light dark:bg-secondary-dark dark:hover:bg-main-dark ring-1 ring-border-light dark:ring-border-dark text-primary-600 dark:text-primary-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                    <canvas id="camera-canvas" class="hidden"></canvas>
                                </div>
                            </div>

                            {{-- Galeri Foto yang sudah diambil (Livewire) --}}
                            @if(count($this->capturedPhotos) > 0)
                                <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-2">
                                    @foreach($this->capturedPhotos as $index => $photo)
                                        <div class="relative p-3 space-y-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                            <button type="button" wire:click="removePhoto({{ $index }})" class="absolute z-10 flex items-center justify-center w-6 h-6 text-white transition-colors bg-red-500 rounded-full shadow hover:bg-red-600 top-4 right-4">
                                                ✕
                                            </button>
                                            <img src="{{ $photo['dataUrl'] }}" class="object-cover w-full h-40 border rounded-lg border-border-light dark:border-border-dark">

                                            <div class="space-y-2">
                                                <select wire:change="updatePhotoType({{ $index }}, $event.target.value)" class="block w-full px-3 py-2 text-sm border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                                                    @foreach($this->getPhotoTypeOptions() as $k => $v)
                                                        <option value="{{ $k }}" {{ ($photo['type'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" wire:change="updatePhotoCaption({{ $index }}, $event.target.value)" value="{{ $photo['caption'] ?? '' }}" placeholder="Keterangan foto..." class="block w-full px-3 py-2 text-sm border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- 2. BAGIAN FORM LAPORAN --}}
                        <div class="p-6 fi-section rounded-2xl ring-1 {{ $this->isCheckInMode() ? 'opacity-50' : '' }}" id="laporan-section">
                            <h2 class="mb-4 text-base font-medium">Laporan Hasil Kunjungan</h2>

                            <div class="space-y-5">
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold">Hasil<span class="text-red-500">*</span></label>
                                    <select wire:model="visit_result" class="w-full px-3 py-3 text-sm border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark focus:border-blue-600 focus:ring-1 focus:ring-blue-600" {{ $this->isCheckInMode() ? 'disabled' : '' }}>
                                        @foreach($this->getResultOptions() as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-semibold">Deskripsi<span class="text-red-500">*</span></label>
                                    <textarea wire:model="description" rows="4" class="w-full px-3 py-3 text-sm border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark focus:border-blue-600 focus:ring-1 focus:ring-blue-600" placeholder="Tuliskan poin-poin hasil pertemuan..." {{ $this->isCheckInMode() ? 'disabled' : '' }}></textarea>
                                </div>

                                <hr class="border-border-light dark:border-border-dark">

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-semibold">Follow Up Berikutnya</label>
                                        <input type="date" wire:model="next_followup_date" class="w-full px-3 py-3 text-sm border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark focus:border-blue-600 focus:ring-1 focus:ring-blue-600" {{ $this->isCheckInMode() ? 'disabled' : '' }}>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="text-sm font-semibold">Catatan Follow Up</label>
                                        <input type="text" wire:model="followup_notes" placeholder="Contoh: Bawa brosur..." class="w-full px-3 py-3 text-sm border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark focus:border-blue-600 focus:ring-1 focus:ring-blue-600" {{ $this->isCheckInMode() ? 'disabled' : '' }}>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex justify-end gap-3 pb-6">
                            <x-filament::button type="button" color="gray" wire:click="back">
                                Batal
                            </x-filament::button>

                            @if($this->isCheckInMode())
                                <x-filament::button
                                    type="button"
                                    id="submitBtn"
                                    color="primary"
                                    wire:loading.attr="disabled">
                                    Check-in Sekarang
                                </x-filament::button>
                            @else
                                <x-filament::button type="submit" id="submitBtn" color="warning" wire:loading.attr="disabled">
                                    Check-out Kunjungan
                                </x-filament::button>
                            @endif
                        </div>
                    </div>

                    {{-- KOLOM KANAN (5 Kolom): MAP & INFO KLIEN --}}
                    <div class="space-y-6 md:col-span-5">
                        {{-- Info Klien / Target --}}
                        <div class="p-6 fi-section rounded-2xl ring-1">
                            <div class="flex items-center gap-4">
                                <div>
                                    <h2 class="text-base font-semibold leading-6">{{ $this->getClientName() }}</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Target Kunjungan</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 mt-6 text-sm">
                                <div class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                    <dt class="text-gray-500 dark:text-gray-400">No. Deal</dt>
                                    <dd class="font-medium">{{ $this->visitAssignment?->deal?->deal_number ?? '-' }}</dd>
                                </div>
                                <div class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                    <dt class="text-gray-500 dark:text-gray-400">Tujuan Kunjungan</dt>
                                    <dd class="font-medium">{{ \App\Models\SalesActivity\VisitAssignment::purposeOptions()[$this->visitAssignment?->purpose] ?? '-' }}</dd>
                                </div>
                                @if($this->isCheckOutMode())
                                <div class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                    <dt class="text-gray-500 dark:text-gray-400">Check-in</dt>
                                    <dd class="font-medium text-success">{{ \Carbon\Carbon::parse($this->visitRecord->check_in_at)->format('H:i') }}</dd>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- MAP LOKASI --}}
                        <div class="p-6 fi-section rounded-2xl ring-1">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h2 class="text-base font-medium">Lokasi GPS</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        @if($this->isCheckInMode())
                                            Kunci lokasi untuk check-in
                                        @else
                                            Lokasi terkunci: {{ \Carbon\Carbon::parse($this->visitRecord->check_in_at)->format('H:i') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs font-medium text-gray-500 border rounded-md border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark" id="accuracy-badge">~</span>
                                </div>
                            </div>

                            <div id="map-container" class="relative overflow-hidden border rounded-2xl border-slate-200 dark:border-gray-700">
                                <div id="map" class="relative h-[250px] w-full bg-slate-100 z-10"></div>
                                {{-- LOADING MAP OVERLAY --}}
                                <div id="map-loading-overlay" class="absolute inset-0 bg-white/70 dark:bg-gray-900/60 backdrop-blur-sm hidden items-center justify-center z-[1000] transition-all">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-10 h-10 border-4 rounded-full border-primary-500 border-t-transparent animate-spin"></div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200" id="map-loading-text">Memuat peta...</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 mt-4 text-sm" id="location-info">
                                <div class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                    <p class="text-gray-500 dark:text-gray-400">Alamat</p>
                                    <p class="font-medium" id="address-text">-</p>
                                </div>
                                <div class="p-4 border rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                    <p class="text-gray-500 dark:text-gray-400">Koordinat</p>
                                    <p class="font-medium" id="coords-text">-</p>
                                </div>

                                <div class="mt-2">
                                    {{-- BUGFIX: gunakan :disabled (bound attribute) — blade 12 bug {{ }} di attribut komponen --}}
                                    <x-filament::button type="button" id="fetchLocationBtn" color="primary" class="w-full" :disabled="$this->isCheckOutMode()">
                                        {{ $this->isCheckInMode() ? 'Kunci Titik Lokasi GPS' : 'Lokasi Terkunci' }}
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </main>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            let map = null;
            let userMarker = null;
            let currentLat = null;
            let currentLng = null;
            let durationTimer = null;
            let isCheckOutMode = @json($this->isCheckOutMode());

            document.addEventListener("livewire:initialized", initVisitRecordPage);
            document.addEventListener("livewire:navigated", initVisitRecordPage);

            function initVisitRecordPage() {
                const mapEl = document.getElementById("map");
                if (!mapEl) return;

                if (map) { map.remove(); map = null; }

                const DEFAULT_CENTER = [-2.5489, 118.0149];
                map = L.map('map').setView(DEFAULT_CENTER, 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                setTimeout(() => map.invalidateSize(), 500);

                const addressText = document.getElementById('address-text');
                const coordsText = document.getElementById('coords-text');
                const accuracyBadge = document.getElementById('accuracy-badge');
                const mapLoadingOverlay = document.getElementById('map-loading-overlay');
                const mapLoadingText = document.getElementById('map-loading-text');
                const fetchLocationBtn = document.getElementById('fetchLocationBtn');

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

                function setButtonState(btn, text, loading = false) {
                    if (!btn) return;
                    btn.disabled = loading;
                    const span = btn.querySelector('span');
                    if (span) span.textContent = text;
                }

                async function updateMapUI(lat, lng, skipDispatch = false) {
                    if (userMarker) map.removeLayer(userMarker);

                    const currentIcon = L.divIcon({
                        html: `<div style="width: 20px; height: 20px; background: #3b82f6; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.5);"></div>`,
                        className: "", iconSize: [20, 20], iconAnchor: [10, 10],
                    });

                    userMarker = L.marker([lat, lng], { icon: currentIcon }).addTo(map);
                    map.setView([lat, lng], 17);

                    currentLat = lat;
                    currentLng = lng;

                    if (coordsText) coordsText.innerText = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    if (addressText) addressText.innerText = 'Memuat alamat...';

                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);
                        const data = await res.json();
                        const addr = data.display_name || 'Alamat tidak ditemukan';
                        if (addressText) addressText.innerText = addr;
                        if (!skipDispatch) {
                            @this.dispatch('gps-received', { lat: lat, lng: lng, address: addr });
                        }
                    } catch {
                        if (addressText) addressText.innerText = 'Gagal mengambil alamat';
                        if (!skipDispatch) {
                            @this.dispatch('gps-received', { lat: lat, lng: lng, address: '' });
                        }
                    }
                }

                // Restore GPS if already checked-in
                const savedLat = @json($this->latitude);
                const savedLng = @json($this->longitude);
                if (savedLat && savedLng) {
                    updateMapUI(savedLat, savedLng, true);
                    if (accuracyBadge) accuracyBadge.innerText = 'GPS Terkunci';
                }

                if (fetchLocationBtn && !isCheckOutMode) {
                    fetchLocationBtn.onclick = () => {
                        if (fetchLocationBtn.disabled) return;
                        setButtonState(fetchLocationBtn, 'Mencari...', true);
                        showMapLoading("Mengunci koordinat satelit...");

                        if (!navigator.geolocation) {
                            alert('Browser tidak mendukung fitur GPS.');
                            hideMapLoading();
                            setButtonState(fetchLocationBtn, 'Kunci Titik Lokasi GPS', false);
                            return;
                        }

                        navigator.geolocation.getCurrentPosition(
                            async (pos) => {
                                await updateMapUI(pos.coords.latitude, pos.coords.longitude);
                                if (accuracyBadge) {
                                    accuracyBadge.innerText = `Akurasi: ±${Math.round(pos.coords.accuracy)}m`;
                                    accuracyBadge.className = "px-2 py-1 text-xs font-medium border rounded-md " + (pos.coords.accuracy < 30 ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700");
                                }
                                hideMapLoading();
                                setButtonState(fetchLocationBtn, 'Update Titik Lokasi', false);
                            },
                            (err) => {
                                hideMapLoading();
                                setButtonState(fetchLocationBtn, 'Coba Lagi', false);
                                alert('Gagal melacak lokasi: ' + err.message);
                            },
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                        );
                    };
                }

                // ─── CAMERA SETUP ───
                const placeholder = document.getElementById('camera-placeholder');
                const cameraLive = document.getElementById('camera-live');
                const video = document.getElementById('camera-video');
                const canvas = document.getElementById('camera-canvas');
                let stream = null;

                const openCameraHandler = async () => {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                        video.srcObject = stream;
                        placeholder.classList.add('hidden');
                        cameraLive.classList.remove('hidden');
                    } catch (err) {
                        alert('Gagal buka kamera: ' + err.message);
                    }
                };

                document.getElementById('openCameraBtn')?.addEventListener('click', openCameraHandler);

                // Auto-open camera in checkout mode so sales can immediately capture photo
                if (isCheckOutMode) {
                    openCameraHandler();
                }

                document.getElementById('captureBtn')?.addEventListener('click', () => {
                    if (!stream) return;
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    const imageData = canvas.toDataURL('image/jpeg', 0.7);

                    @this.dispatch('photo-captured', {
                        photo: {
                            dataUrl: imageData,
                            lat: currentLat,
                            lng: currentLng,
                            type: 'documentation',
                            caption: ''
                        }
                    });
                });

                document.addEventListener('livewire:navigating', () => {
                    if (stream) {
                        stream.getTracks().forEach(t => t.stop());
                        stream = null;
                    }
                }, { once: true });

                // ─── SUBMIT HANDLER (Check-in or Checkout) ───
                const submitBtn = document.getElementById('submitBtn');
                const form = document.getElementById('visit-form');

                if (submitBtn && form) {
                    submitBtn.onclick = (e) => {
                        e.preventDefault();
                        if (submitBtn.disabled) return;

                        if (!currentLat || !currentLng) {
                            alert("Anda harus Mengunci Titik Lokasi GPS terlebih dahulu sebelum melanjutkan!");
                            document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }

                        setButtonState(submitBtn, 'Menyimpan...', true);

                        if (isCheckOutMode) {
                            // Checkout flow
                            @this.call('checkOut').then(() => {
                                setButtonState(submitBtn, 'Check-out Kunjungan', false);
                                clearInterval(durationTimer);
                            });
                        } else {
                            // Check-in flow
                            @this.call('checkIn').then(() => {
                                setButtonState(submitBtn, 'Check-in', false);
                                // Reload state dan redirect ke checkout mode
                                @this.call('reloadAfterCheckIn').then(() => {
                                    window.location.href = @json(\App\Filament\Pages\SalesActivity\RecordVisitPage::getUrl(['assignment' => $this->assignment]));
                                });
                            });
                        }
                    };
                }

                // ─── DURATION TIMER (checkout mode only) ───
                const isCheckoutMode = @json($this->isCheckOutMode());
                const checkInTimestamp = @json($this->checkInTime());
                if (isCheckoutMode && checkInTimestamp) {
                    const timerEl = document.getElementById('duration-timer');
                    if (timerEl) {
                        const checkInTime = new Date(checkInTimestamp);

                        function updateDuration() {
                            const diff = Date.now() - checkInTime.getTime();
                            const m = Math.floor(diff / 60000);
                            const s = Math.floor((diff % 60000) / 1000);
                            timerEl.innerText = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                        }

                        updateDuration();
                        if (durationTimer) clearInterval(durationTimer);
                        durationTimer = setInterval(updateDuration, 1000);
                    }
                }
            }
        </script>
    @endpush
</x-filament-panels::page>
