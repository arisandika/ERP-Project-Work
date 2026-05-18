<div>
    {{--
    resources/views/filament/infolists/visit-photos.blade.php
    --}}
    @php
        $record = $getRecord();
        
        // BUNGKUS DENGAN collect() AGAR DIJAMIN MENJADI COLLECTION YANG VALID
        // Jika $record null atau $record->photos null, ini akan menjadi Collection kosong []
        $photos = collect($record?->photos ?? []);
    @endphp

    @if($photos->isEmpty())
        <p class="text-sm italic text-gray-400 dark:text-gray-500">Tidak ada foto untuk kunjungan ini.</p>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            @foreach($photos as $photo)
                <div class="relative overflow-hidden border shadow-sm cursor-pointer group rounded-xl border-border-light dark:border-border-dark"
                    onclick="openVisitPhotoLightbox('{{ $photo->url() }}', '{{ addslashes($photo->caption ?? '') }}', '{{ $photo->taken_at?->format('d M Y H:i') ?? '' }}', '{{ $photo->latitude ?? '' }}', '{{ $photo->longitude ?? '' }}')">
                    
                    <img src="{{ $photo->url() }}" alt="{{ $photo->caption ?? 'Foto kunjungan' }}"
                        class="object-cover w-full h-32 transition-transform duration-200 group-hover:scale-105">

                    <div class="absolute top-1.5 left-1.5">
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md
                                {{ match ($photo->photo_type) {
                                    'documentation' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'evidence' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'location' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                } }}">
                            {{ \App\Models\SalesActivity\VisitPhoto::typeOptions()[$photo->photo_type] ?? $photo->photo_type }}
                        </span>
                    </div>

                    @if($photo->hasCoordinates())
                        <div class="absolute bottom-1.5 right-1.5">
                            <a href="{{ $photo->googleMapsUrl() }}" target="_blank" rel="noopener noreferrer"
                                onclick="event.stopPropagation()"
                                class="inline-flex items-center gap-1 text-[10px] font-medium bg-white/90 dark:bg-gray-900/80 text-gray-700 dark:text-gray-200 px-1.5 py-0.5 rounded-md shadow hover:bg-white">
                                <svg class="w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd" />
                                </svg>
                                GPS
                            </a>
                        </div>
                    @endif

                    @if($photo->caption)
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-2 pt-4 pb-1.5">
                            <p class="text-[11px] text-white line-clamp-1">{{ $photo->caption }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- SCRIPT LIGHTBOX TETAP SAMA SEPERTI SEBELUMNYA --}}
    @once
        <div id="visit-photo-lightbox" class="fixed inset-0 z-[9999] bg-black/85 hidden items-center justify-center p-4"
            onclick="closeVisitPhotoLightbox()">
            <div class="relative w-full max-w-3xl" onclick="event.stopPropagation()">
                <button onclick="closeVisitPhotoLightbox()"
                    class="absolute right-0 flex items-center gap-1 text-sm -top-10 text-white/70 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Tutup
                </button>
                <img id="visit-lightbox-img" src="" alt="" class="w-full max-h-[75vh] object-contain rounded-xl shadow-2xl">
                <div class="mt-3 space-y-1 text-center">
                    <p id="visit-lightbox-caption" class="text-sm font-medium text-white"></p>
                    <p id="visit-lightbox-time" class="text-xs text-white/60"></p>
                    <a id="visit-lightbox-maps" href="#" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center hidden gap-1 mt-1 text-xs text-blue-300 hover:text-blue-200">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                clip-rule="evenodd" />
                        </svg>
                        Lihat di Google Maps
                    </a>
                </div>
            </div>
        </div>

        <script>
            function openVisitPhotoLightbox(url, caption, time, lat, lng) {
                document.getElementById('visit-lightbox-img').src = url;
                document.getElementById('visit-lightbox-caption').textContent = caption || '';
                document.getElementById('visit-lightbox-time').textContent = time ? '📅 ' + time : '';
                const mapsLink = document.getElementById('visit-lightbox-maps');
                if (lat && lng) {
                    mapsLink.href = 'https://www.google.com/maps?q=' + lat + ',' + lng;
                    mapsLink.classList.remove('hidden');
                } else {
                    mapsLink.classList.add('hidden');
                }
                const lb = document.getElementById('visit-photo-lightbox');
                lb.classList.remove('hidden');
                lb.classList.add('flex');
            }
            function closeVisitPhotoLightbox() {
                const lb = document.getElementById('visit-photo-lightbox');
                lb.classList.add('hidden');
                lb.classList.remove('flex');
                document.getElementById('visit-lightbox-img').src = '';
            }
            document.addEventListener('keydown', e => { if (e.key === 'Escape') closeVisitPhotoLightbox(); });
        </script>
    @endonce
</div>