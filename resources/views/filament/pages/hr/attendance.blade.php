<x-filament-panels::page>
    @push('styles')
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
    @endpush

    <main class="max-w-6xl">
        <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:col-span-3">

                @if($employee)
                    <div
                        class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex items-center justify-center w-12 h-12 overflow-hidden rounded-full bg-slate-100">
                                @if($employee->photo)
                                    <img src="{{ asset('storage/' . $employee->photo) }}" alt="Foto"
                                        class="object-cover w-12 h-12 rounded-full">
                                @else
                                    <img src="{{ url('/assets/placeholder.jpg') }}" alt="Foto"
                                        class="object-cover w-12 h-12 rounded-full">
                                @endif
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold leading-6">{{ $employee->full_name }}</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->position ?? '-' }}</p>
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-4 mt-6 text-sm">
                            <div
                                class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Departemen</dt>
                                <dd class="font-medium">{{ $employee->department->name ?? '-' }}</dd>
                            </div>
                            <div
                                class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Kantor</dt>
                                <dd class="font-medium">{{ $office->name ?? '-' }}</dd>
                            </div>
                            <div
                                class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Jadwal</dt>
                                <dd class="font-medium">
                                    {{ $employee->shift->name ?? '-' }}
                                    ({{ isset($employee->shift->start_time) ? \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i') : '-' }}
                                    -
                                    {{ isset($employee->shift->end_time) ? \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i') : '-' }})
                                </dd>
                            </div>
                            <div
                                class="p-3 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Tipe Karyawan</dt>
                                <dd class="font-medium">
                                    @if($employee->can_wfa == 1)
                                        Bekerja dari rumah
                                    @else
                                        Bekerja dari kantor
                                    @endif
                                    &
                                    @if($employee->can_unlock_shift == 1)
                                        Jam kerja fleksibel
                                    @else
                                        Jam kerja tetap
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div
                        class="p-6 text-center text-gray-500 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        Tidak ada data karyawan untuk user ini.
                    </div>
                @endif

                @if(!$hasCheckedIn)
                    <form action="{{ route('attendance.clockin') }}" method="POST"
                        class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                        aria-labelledby="clock-in-title">
                        @csrf
                        <h2 id="clock-in-title" class="text-base font-medium">Siap presensi masuk?</h2>
                        <div class="grid gap-4 mt-4">
                            <div class="grid gap-1">
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
                                        {{ isset($employee->shift->start_time) ? \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i') : '-' }}
                                        -
                                        {{ isset($employee->shift->end_time) ? \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i') : '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 mt-4">

                                <x-filament::button type="button" id="presensiMasukButton" aria-label="Presensi sekarang"
                                    color="primary">
                                    <span>Tandai Lokasi</span>
                                </x-filament::button>
                                <x-filament::button tag="a" href="#" color="gray"> Lihat Riwayat </x-filament::button>
                            </div>
                        </div>

                        <input type="hidden" name="lat" id="lat" />
                        <input type="hidden" name="lng" id="lng" />
                    </form>
                @elseif($hasCheckedIn && !$hasCheckedOut)
                    <form action="{{ route('attendance.clockout') }}" method="POST"
                        class="p-6 bg-white fi-section rounded-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                        aria-labelledby="clock-out-title" onsubmit="return setLocationBeforeSubmit(event)">
                        @csrf
                        <h2 id="clock-out-title" class="text-base font-medium">Sudah selesai kerja?</h2>

                        <div class="grid gap-4 mt-4">
                            @if($attendanceToday)
                                <div
                                    class="flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Jam masuk</p>
                                        <p class="text-sm font-medium">
                                            {{ \Carbon\Carbon::parse($attendanceToday->clock_in)->format('H:i') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                                        <p class="text-sm font-medium">
                                            {{ ucfirst($attendanceToday->status) }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                            <div
                                class="flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg fflex bg-gray-50 dark:bg-gray-800/50 dark:border-white/10">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Waktu saat ini</p>
                                    <p class="text-sm font-medium" id="current-time-clockout"></p>
                                    <script> function updateTimeClockOut() { const now = new Date(); const hours = now.getHours().toString().padStart(2, '0'); const minutes = now.getMinutes().toString().padStart(2, '0'); const seconds = now.getSeconds().toString().padStart(2, '0'); const ampm = hours >= 12 ? 'PM' : 'AM'; document.getElementById('current-time-clockout').textContent = `${hours}:${minutes}:${seconds} ${ampm}`; } setInterval(updateTimeClockOut, 1000); updateTimeClockOut(); </script>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Jam Kerja</p>
                                    <p class="text-sm font-medium">
                                        {{ isset($employee->shift->start_time) ? \Carbon\Carbon::parse($employee->shift->start_time)->format('H:i') : '-' }}
                                        -
                                        {{ isset($employee->shift->end_time) ? \Carbon\Carbon::parse($employee->shift->end_time)->format('H:i') : '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 mt-4">
                            <x-filament::button type="submit" color="danger" aria-label="Presensi keluar sekarang"
                                id="presensiKeluarButton">
                                <span>Presensi Keluar</span>
                            </x-filament::button>
                            <x-filament::button tag="a" href="#" color="gray"> Lihat Riwayat </x-filament::button>
                        </div>

                        <input type="hidden" name="lat" id="lat-out" />
                        <input type="hidden" name="lng" id="lng-out" />
                    </form>
                @else
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
                        <x-filament::button tag="a" href="#" color="primary" icon="heroicon-o-clock">
                            Lihat Riwayat Presensi
                        </x-filament::button>
                    </div>
                @endif
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
        // --- LEAFLET MAP INITIALIZATION ---
        const map = L.map('map').setView([{{ $employee->office->latitude }}, {{ $employee->office->longitude }}], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        setTimeout(() => {
            map.invalidateSize();
        }, 500);

        const officeCenter = [{{ $employee->office->latitude }}, {{ $employee->office->longitude }}];
        const officeRadius = {{ $office->radius_meters }};
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

        // --- UX OPTIMIZATION JAVASCRIPT LOGIC ---
        let isLocationReadyForSubmission = false; // Flag to indicate if location is successfully obtained for clock-in

        // Clock-in elements
        const presensiMasukForm = document.querySelector('form[action="{{ route('attendance.clockin') }}"]');
        const presensiMasukButton = document.getElementById('presensiMasukButton');
        const latInputClockIn = document.getElementById('lat');
        const lngInputClockIn = document.getElementById('lng');
        const mapContainer = document.getElementById('map'); // Target to scroll to

        // Clock-out elements
        const presensiKeluarForm = document.querySelector('form[action="{{ route('attendance.clockout') }}"]');
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
            // This function is called when the form is submitted via the now 'submit' type button
            if (!isLocationReadyForSubmission || latInputClockIn.value === '' || lngInputClockIn.value === '') {
                event.preventDefault(); // Stop submission
                alert('Lokasi belum ditandai atau gagal didapatkan. Silakan coba "Tandai Lokasi" lagi.');
                updatePresensiMasukButtonState('Tandai Lokasi'); // Revert button state
                isLocationReadyForSubmission = false;
                presensiMasukButton.onclick = handleTagLocationClick; // Re-attach the tag location handler
                return;
            }

            updatePresensiMasukButtonState('Mengirim Presensi', true);
        }
        async function setLocationBeforeSubmit(event) {
            event.preventDefault(); // Stop the form from submitting immediately

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
    </script>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    @endpush
</x-filament-panels::page>