<x-filament-panels::page>
    @php
        $assignment = $this->visitAssignment;
        $deal = $this->deal;
        $records = $this->visit_records;
    @endphp

    <div class="w-full">
        <main class="max-w-7xl">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                
                {{-- ── KOLOM KIRI: MAIN CONTENT (8 Kolom) ────────────────────────── --}}
                <div class="space-y-6 md:col-span-8">
                    
                    {{-- Bagian Catatan / Instruksi --}}
                    @if($assignment->notes)
                        <div class="p-6 fi-section rounded-2xl ring-1">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-secondary-light dark:bg-secondary-dark ring-1 ring-border-light dark:ring-border-dark">
                                    <x-heroicon-o-document-text class="w-5 h-5 text-gray-700 dark:text-gray-400" />
                                </div>
                                <h2 class="text-base font-semibold">Instruksi / Catatan Penugasan</h2>
                            </div>
                            <div class="p-4 text-sm text-gray-700 whitespace-pre-wrap border rounded-xl bg-main-light border-border-light dark:bg-accent-dark dark:border-border-dark dark:text-gray-300">
                                {{ $assignment->notes }}
                            </div>
                        </div>
                    @endif

                    {{-- Bagian Timeline Riwayat Kunjungan --}}
                    <div class="p-6 fi-section rounded-2xl ring-1">
                        <div class="mb-6">
                            <h2 class="mb-1 text-base font-semibold">Riwayat Pelaksanaan Kunjungan</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Daftar rekaman kunjungan yang telah dilakukan.</p>
                        </div>
                        
                        @if($records->isEmpty())
                            <div class="flex flex-col items-center justify-center py-10 text-center border border-dashed rounded-2xl border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark">
                                <x-heroicon-o-map-pin class="w-12 h-12 mb-3 text-gray-400 dark:text-gray-500" />
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Kunjungan</h3>
                                <p class="mt-1 text-xs text-gray-500 max-w-[300px] dark:text-gray-400">
                                    Gunakan tombol "Rekam Kunjungan" di kanan atas untuk memulai check-in di lokasi klien.
                                </p>
                            </div>
                        @else
                            {{-- Alpine Component untuk Lightbox Foto --}}
                            <div x-data="{ 
                                    lightboxOpen: false, 
                                    lightboxImg: '', 
                                    lightboxCaption: '',
                                    openGallery(url, caption) {
                                        this.lightboxImg = url;
                                        this.lightboxCaption = caption;
                                        this.lightboxOpen = true;
                                    }
                                }" 
                                class="relative"
                            >
                                {{-- Garis Vertikal Timeline --}}
                                <div class="absolute left-0 w-px h-full mt-2 bg-border-light dark:bg-border-dark" style="left: 0.35rem;"></div>

                                <div class="pl-8 space-y-8">
                                    @foreach($records as $record)
                                        <div class="relative">
                                            {{-- Dot Timeline --}}
                                            <div class="absolute -left-[32px] w-3 h-3 mt-1.5 bg-blue-600 rounded-full ring-4 ring-white dark:ring-gray-900 dark:bg-blue-500"></div>

                                            {{-- Header Kunjungan --}}
                                            <div>
                                                <div class="flex items-center justify-between">
                                                    <h3 class="font-bold text-gray-900 text-md dark:text-white">
                                                        Kunjungan ke-{{ $record->visit_order }}
                                                    </h3>
                                                    <span class="text-xs font-medium">
                                                        {{ $record->visited_at ? $record->visited_at->format('d M Y, H:i') : '-' }}
                                                    </span>
                                                </div>

                                                <div class="flex flex-col gap-3 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                    @if($record->location_address)
                                                        <a href="{{ $record->googleMapsUrl() }}" target="_blank" class="flex items-start gap-1 transition-colors hover:text-orange-600 dark:hover:text-orange-400">
                                                            <x-heroicon-o-map-pin class="w-4 h-4 mt-0.5" />
                                                            <span class="">{{ $record->location_address }}</span>
                                                        </a>
                                                    @endif

                                                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                        Hasil Kunjungan:
                                                        <span class="px-2 py-0.5 text-sm font-medium  rounded-md {{ $this->getResultColorClass($record->visit_result) }}">
                                                        {{ \App\Models\SalesActivity\VisitRecord::resultOptions()[$record->visit_result] ?? $record->visit_result }}
                                                    </span>
                                                    </div>

                                                    {{-- Check-in / Check-out / Duration --}}
                                                    @if($record->check_in_at || $record->check_out_at)
                                                        <div class="flex flex-wrap gap-4 mt-2 text-xs">
                                                            @if($record->check_in_at)
                                                                <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                                                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                                                    <span>Check-in:</span>
                                                                    <span class="font-medium text-gray-800 dark:text-gray-300">{{ \Carbon\Carbon::parse($record->check_in_at)->format('H:i') }}</span>
                                                                </div>
                                                            @endif
                                                            @if($record->check_out_at)
                                                                <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                                                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                                                    <span>Check-out:</span>
                                                                    <span class="font-medium text-gray-800 dark:text-gray-300">{{ \Carbon\Carbon::parse($record->check_out_at)->format('H:i') }}</span>
                                                                </div>
                                                            @endif
                                                            @if(!is_null($record->duration_minutes))
                                                                <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                                                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                                                    <span>Durasi:</span>
                                                                    <span class="font-medium text-gray-800 dark:text-gray-300">{{ $record->duration_minutes }} menit</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    {{-- Visit purpose (contextual) --}}
                                                    @if($record->visit_purpose && $record->visit_purpose !== 'new_prospect')
                                                        <div class="flex items-center gap-1 mt-1 text-xs">
                                                            <x-heroicon-o-flag class="w-3.5 h-3.5 text-blue-500" />
                                                            <span class="text-gray-600 dark:text-gray-400">
                                                                Tujuan:
                                                                @if($record->project)
                                                                    Sedang menangani {{ $record->project->name }}
                                                                @else
                                                                    {{ $record->visit_purpose }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @elseif($record->visit_purpose === 'new_prospect')
                                                        <div class="flex items-center gap-1 mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                            <x-heroicon-o-flag class="w-3.5 h-3.5 text-blue-500" />
                                                            <span>Tujuan: Menawarkan Produk/Prospek Baru</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if($record->description)
                                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                                        Deskripsi: {{ $record->description }}
                                                    </p>
                                                @endif

                                                {{-- Follow Up Section (Jika Ada) --}}
                                                @if($record->next_followup_date || $record->followup_notes)
                                                    <div class="p-3 mt-3 text-xs border rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 border-amber-200 dark:border-amber-800/50">
                                                        @if($record->next_followup_date)
                                                            <div class="flex items-center gap-1 mb-1 font-semibold">
                                                                <x-heroicon-o-calendar-days class="w-4 h-4" />
                                                                Follow-up berikutnya: {{ \Carbon\Carbon::parse($record->next_followup_date)->format('d M Y') }}
                                                            </div>
                                                        @endif
                                                        @if($record->followup_notes)
                                                            <p class="opacity-90">{{ $record->followup_notes }}</p>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Grid Foto --}}
                                            <div class="mt-4">
                                                @if($record->photos && $record->photos->count() > 0)
                                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                                        @foreach($record->photos as $photo)
                                                            <div 
                                                                @click="openGallery('{{ $photo->url() }}', '{{ addslashes($photo->caption ?? 'Foto #' . $loop->iteration) }}')"
                                                                class="relative overflow-hidden transition-all border cursor-pointer rounded-xl group border-border-light dark:border-border-dark aspect-square hover:ring-2 hover:ring-blue-500"
                                                            >
                                                                <img 
                                                                    src="{{ $photo->url() }}" 
                                                                    alt="Foto" 
                                                                    class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-110"
                                                                >
                                                                <div class="absolute inset-0 transition-opacity bg-black opacity-0 group-hover:opacity-10"></div>
                                                                
                                                                <div class="absolute top-1 left-1">
                                                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm bg-white/90 text-gray-800 uppercase tracking-wider">
                                                                        {{ \App\Models\SalesActivity\VisitPhoto::typeOptions()[$photo->photo_type] ?? $photo->photo_type }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="px-3 py-2 text-xs italic text-gray-500 border rounded-xl border-border-light bg-main-light dark:bg-accent-dark dark:text-gray-400 dark:border-border-dark w-fit">
                                                        Tidak ada foto dilampirkan.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Modal Lightbox Foto --}}
                                <div id="image-preview-modal" x-show="lightboxOpen" style="display: none;" class="fixed inset-0 z-[2000] flex items-center justify-center bg-black/70 backdrop-blur-sm"
                                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                    @keydown.escape.window="lightboxOpen = false">
                                    <div class="relative w-full max-w-3xl mx-4" @click.away="lightboxOpen = false">
                                        <button @click="lightboxOpen = false" class="absolute z-50 flex items-center justify-center w-8 h-8 text-gray-800 bg-white rounded-full shadow -top-3 -right-3 hover:bg-gray-200">
                                            ✕
                                        </button>
                                        <img :src="lightboxImg" class="w-full max-h-[80vh] object-contain rounded-lg shadow-xl bg-black" />
                                        <p x-text="lightboxCaption" class="mt-4 text-sm text-center text-gray-300"></p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── KOLOM KANAN: SIDEBAR INFO (4 Kolom) ────────────────────────── --}}
                <div class="space-y-6 md:col-span-4">
                    
                    {{-- Status Tugas --}}
                    <div class="p-6 fi-section rounded-2xl ring-1">
                        <div class="flex items-center justify-between pb-4">
                            <span class="text-base font-semibold">Status</span>
                            <span class="px-2.5 py-1 text-xs font-medium rounded-md {{ $this->status_color }}">
                                {{ \App\Models\SalesActivity\VisitAssignment::statusOptions()[$assignment->status] ?? $assignment->status }}
                            </span>
                        </div>

                        @if($this->is_checked_in && $this->active_visit_record)
                            @php
                                $durationMin = $this->active_visit_record->durationMinutes();
                            @endphp
                            <div class="p-4 border border-orange-200 bg-orange-50 dark:bg-orange-900/20 dark:border-orange-800/50 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-clock class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                                        <span class="font-semibold text-orange-800 dark:text-orange-200">Sedang dalam kunjungan</span>
                                    </div>
                                    @if($durationMin)
                                        <span class="text-xs font-medium text-orange-700 dark:text-orange-300">
                                            {{ floor($durationMin / 60) }}j {{ $durationMin % 60 }}m
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-orange-700 dark:text-orange-300">
                                    Check-in: {{ \Carbon\Carbon::parse($this->active_visit_record->check_in_at)->format('H:i') }}
                                </p>
                            </div>
                        @endif

                        <div class="mt-4 space-y-4">
                            <div class="p-4 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                <span class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Tujuan Kunjungan</span>
                                <span class="inline-flex items-center gap-1.5 font-medium text-sm text-gray-900 dark:text-white">
                                    <x-heroicon-o-flag class="w-4 h-4 text-blue-500" />
                                    {{ \App\Models\SalesActivity\VisitAssignment::purposeOptions()[$assignment->purpose] ?? $assignment->purpose }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="p-4 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <span class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Tgl Rencana</span>
                                    <span class="block font-medium text-gray-900 dark:text-white">
                                        {{ $assignment->visit_date ? \Carbon\Carbon::parse($assignment->visit_date)->format('d M') : '-' }}
                                    </span>
                                </div>
                                <div class="p-4 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <span class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Deadline</span>
                                    <span class="block font-medium {{ $assignment->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ $assignment->deadline_date ? \Carbon\Carbon::parse($assignment->deadline_date)->format('d M') : '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Detail Klien --}}
                    <div class="p-6 fi-section rounded-2xl ring-1">
                        <div class="flex items-center gap-3 mb-4">
                            <h2 class="text-base font-semibold">Informasi Klien</h2>
                        </div>
                        
                        <div class="p-4 space-y-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                            <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $this->client_name }}</h4>

                            @if($this->client_phone)
                                <div class="flex items-start gap-2 text-sm">
                                    <x-heroicon-o-phone class="w-4 h-4 shrink-0 mt-0.5 text-gray-500" />
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $this->client_phone) }}" target="_blank" class="text-green-600 dark:text-green-400 hover:underline">
                                        {{ $this->client_phone }}
                                    </a>
                                </div>
                            @endif

                            @if($this->client_email)
                                <div class="flex items-start gap-2 text-sm">
                                    <x-heroicon-o-envelope class="w-4 h-4 shrink-0 mt-0.5 text-gray-500" />
                                    <a href="mailto:{{ $this->client_email }}" class="break-all text-warning-600 dark:text-warning-400 hover:underline">
                                        {{ $this->client_email }}
                                    </a>
                                </div>
                            @endif

                            @if($this->client_address)
                                <div class="flex items-start gap-2 text-sm">
                                    <x-heroicon-o-map-pin class="w-4 h-4 shrink-0 mt-0.5 text-gray-500" />
                                    <span class="text-gray-700 dark:text-gray-300">{{ $this->client_address }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Detail Deal --}}
                    @if($deal)
                        <div class="p-6 fi-section rounded-2xl ring-1">
                            <div class="flex items-center gap-3 mb-4">
                                <h2 class="text-base font-semibold">Informasi Deal</h2>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3 text-sm">
                                <div class="p-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <dt class="mb-1 text-xs text-gray-500 dark:text-gray-400">No. Deal</dt>
                                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $deal->deal_number }}</dd>
                                </div>
                                <div class="p-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <dt class="mb-1 text-xs text-gray-500 dark:text-gray-400">Judul Penawaran</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">{{ $deal->title }}</dd>
                                </div>
                                <div class="p-3 border border-border-light bg-main-light dark:bg-accent-dark dark:border-border-dark rounded-xl">
                                    <dt class="mb-2 text-xs text-gray-500 dark:text-gray-400">Stage Saat Ini</dt>
                                    <dd>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $deal->stage?->name ?? 'Unknown' }}
                                        </span>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </main>
    </div>
</x-filament-panels::page>