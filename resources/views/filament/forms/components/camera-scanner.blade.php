{{-- resources/views/filament/forms/components/camera-scanner.blade.php --}}
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
<style>
[x-cloak] { display: none !important; }

#sc-wrap-{{ $getId() }} video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    display: block !important;
    transform-origin: center center;
    transition: transform 0.15s ease;
}
@keyframes sweep {
    0%   { top: 10%; opacity: 0; }
    8%   { opacity: 1; }
    92%  { opacity: 1; }
    100% { top: 83%; opacity: 0; }
}
@keyframes ping-out {
    0%   { transform: scale(.8); opacity: 1; }
    100% { transform: scale(2.2); opacity: 0; }
}
@keyframes cblink {
    0%, 100% { opacity: 1; }
    50%       { opacity: .35; }
}
.sc-sweep  { animation: sweep 2s cubic-bezier(.4,0,.6,1) infinite; }
.sc-ping   { animation: ping-out .55s ease-out forwards; }
.sc-corner { animation: cblink 2.4s ease-in-out infinite; }

.zoom-slider {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 3px;
    border-radius: 2px;
    background: rgba(255,255,255,0.25);
    outline: none;
    cursor: pointer;
}
.zoom-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 4px rgba(74,222,128,.6);
    cursor: pointer;
    transition: transform .1s;
}
.zoom-slider::-webkit-slider-thumb:active { transform: scale(1.25); }
.zoom-slider::-moz-range-thumb {
    width: 18px; height: 18px;
    border-radius: 50%;
    background: #4ade80;
    border: none;
    cursor: pointer;
}
</style>

<div
    x-data="{
        isScanning:    false,
        isLoading:     false,
        scanSuccess:   false,
        lastResult:    '',
        controls:      null,
        statePath:     '{{ $getStatePath() }}',
        zoomLevel:     1,
        zoomMin:       1,
        zoomMax:       4,
        zoomStep:      0.1,
        hwZoomSupport: false,
        videoTrack:    null,

        init() {
            $wire.set(this.statePath, null);
        },

        loadScript() {
            return new Promise((resolve, reject) => {
                if (window.ZXingBrowser) { resolve(); return; }
                const existing = document.querySelector('script[data-zxing]');
                if (existing) {
                    existing.addEventListener('load',  resolve);
                    existing.addEventListener('error', reject);
                    return;
                }
                const s = document.createElement('script');
                s.src           = 'https://unpkg.com/@zxing/browser@latest';
                s.dataset.zxing = '1';
                s.onload        = resolve;
                s.onerror       = () => reject(new Error('Gagal memuat ZXing.'));
                document.head.appendChild(s);
            });
        },

        async openScanner() {
            this.isScanning   = true;
            this.isLoading    = true;
            this.scanSuccess  = false;
            this.zoomLevel    = 1.5;

            try {
                await this.loadScript();
            } catch(e) {
                this.isScanning = false;
                this.isLoading  = false;
                alert('Gagal memuat library scanner. Periksa koneksi internet.');
                return;
            }

            await this.$nextTick();
            await this.startScan();
        },

        async startScan() {
            const videoEl = document.getElementById('zxvid-{{ $getId() }}');
            if (!videoEl) {
                this.isScanning = false;
                this.isLoading  = false;
                return;
            }

            try {
                const codeReader = new ZXingBrowser.BrowserMultiFormatReader(null, {
                    delayBetweenScanAttempts: 80,
                    delayBetweenScanSuccess:  1200,
                });

                const devices = await ZXingBrowser.BrowserCodeReader.listVideoInputDevices();
                if (!devices.length) throw new Error('Tidak ada kamera ditemukan.');

                const backCamera = devices.find(d =>
                    /back|rear|environment/i.test(d.label)
                ) ?? devices[devices.length - 1];

                this.controls = await codeReader.decodeFromConstraints(
                    {
                        video: {
                            deviceId:  backCamera.deviceId ? { exact: backCamera.deviceId } : undefined,
                            facingMode: { ideal: 'environment' },
                            width:      { ideal: 1920 },
                            height:     { ideal: 1080 },
                            focusMode:  { ideal: 'continuous' },
                        }
                    },
                    videoEl,
                    (result) => { if (result) this.onDecoded(result.getText()); }
                );

                const stream = videoEl.srcObject;
                if (stream) {
                    this.videoTrack = stream.getVideoTracks()[0] ?? null;
                    this.detectHwZoom();
                }

                await this.$nextTick();
                this.applyZoom(this.zoomLevel);
                this.isLoading = false;

            } catch(e) {
                this.isLoading  = false;
                this.isScanning = false;
                alert('Gagal membuka kamera: ' + (e?.message ?? 'Unknown error') +
                      '. Pastikan izin kamera sudah diberikan.');
            }
        },

        detectHwZoom() {
            if (!this.videoTrack) return;
            try {
                const caps = this.videoTrack.getCapabilities?.() ?? {};
                if (caps.zoom && caps.zoom.min !== undefined) {
                    this.hwZoomSupport = true;
                    this.zoomMin   = caps.zoom.min;
                    this.zoomMax   = Math.min(caps.zoom.max, 8);
                    this.zoomStep  = caps.zoom.step ?? 0.1;
                    this.zoomLevel = Math.min(Math.max(caps.zoom.min * 1.5, caps.zoom.min), this.zoomMax);
                }
            } catch(e) {}
        },

        applyZoom(val) {
            this.zoomLevel = parseFloat(val);
            if (this.hwZoomSupport && this.videoTrack) {
                try {
                    this.videoTrack.applyConstraints({ advanced: [{ zoom: this.zoomLevel }] });
                    return;
                } catch(e) { this.hwZoomSupport = false; }
            }
            const videoEl = document.getElementById('zxvid-{{ $getId() }}');
            if (videoEl) {
                videoEl.style.transform       = `scale(${this.zoomLevel})`;
                videoEl.style.transformOrigin = 'center center';
            }
        },

        zoomIn()  { this.applyZoom(Math.min(+(this.zoomLevel + this.zoomStep * 5).toFixed(2), this.zoomMax)); },
        zoomOut() { this.applyZoom(Math.max(+(this.zoomLevel - this.zoomStep * 5).toFixed(2), this.zoomMin)); },

        get zoomPercent() {
            return Math.round(Math.max(0, Math.min(100,
                ((this.zoomLevel - this.zoomMin) / (this.zoomMax - this.zoomMin)) * 100
            )));
        },
        get zoomLabel() { return parseFloat(this.zoomLevel).toFixed(1) + '×'; },

        onDecoded(text) {
            if (this.scanSuccess) return;
            this.scanSuccess = true;
            this.lastResult  = text;
            $wire.set(this.statePath, text);
            setTimeout(() => {
                this.scanSuccess = false;
                this.lastResult  = '';
                $wire.set(this.statePath, null);
            }, 1500);
        },

        stopScan() {
            if (this.controls) {
                try { this.controls.stop(); } catch(e) {}
                this.controls = null;
            }
            const videoEl = document.getElementById('zxvid-{{ $getId() }}');
            if (videoEl) videoEl.style.transform = '';
            this.videoTrack    = null;
            this.hwZoomSupport = false;
            this.zoomLevel     = 1;
            this.isScanning    = false;
            this.isLoading     = false;
            this.scanSuccess   = false;
        }
    }"
    x-on:livewire:navigating.window="stopScan()"
    id="sc-wrap-{{ $getId() }}"
    class="w-full"
>

    {{-- ── TOMBOL BUKA SCANNER ── --}}
    <button
        type="button"
        x-show="!isScanning"
        x-on:click="openScanner()"
        class="flex flex-col items-center justify-center w-full gap-2 px-4 transition-all border-2 border-dashed cursor-pointer py-7 border-primary-400 rounded-xl bg-primary-50 hover:bg-primary-100 dark:bg-gray-800 dark:border-primary-500 dark:hover:bg-gray-700 group"
    >
        <div class="p-3 transition-transform bg-white rounded-full shadow dark:bg-gray-700 text-primary-600 dark:text-primary-400 group-hover:scale-110">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9" fill="none"
                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5
                         c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5z
                         M3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5
                         c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z
                         M13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5
                         c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75V16.5z
                         M16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75z
                         M13.5 18.75h.75v.75h-.75v-.75zM16.5 13.5h.75v.75h-.75v-.75z
                         M16.5 18.75h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75z
                         M19.5 18.75h.75v.75h-.75v-.75z"/>
            </svg>
        </div>
        <div class="text-center">
            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">Buka Scanner Barcode / QR</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tap untuk aktifkan kamera</p>
        </div>
    </button>

    {{-- ── AREA KAMERA ── --}}
    {{-- PENTING: satu style attribute, height wajib ada, x-cloak untuk hide sebelum Alpine init --}}
    <div
        x-cloak
        x-show="isScanning"
        class="relative w-full overflow-hidden bg-black border border-gray-700 shadow-xl rounded-xl"
        style="height: 400px;"
    >
        <video
            id="zxvid-{{ $getId() }}"
            class="absolute inset-0 w-full h-full"
            muted
            playsinline
        ></video>

        {{-- Dim overlay --}}
        <div
            x-show="!isLoading"
            class="absolute inset-0 pointer-events-none"
            style="background:
                linear-gradient(to bottom, rgba(0,0,0,.52) 9%,  transparent 9%),
                linear-gradient(to top,    rgba(0,0,0,.52) 19%, transparent 19%),
                linear-gradient(to right,  rgba(0,0,0,.52) 6%,  transparent 6%),
                linear-gradient(to left,   rgba(0,0,0,.52) 6%,  transparent 6%);"
        ></div>

        {{-- Scan line --}}
        <div
            x-show="!isLoading && !scanSuccess"
            class="sc-sweep absolute left-[6%] right-[6%] pointer-events-none"
            style="height: 1px;
                   background: linear-gradient(90deg,transparent,#4ade80 25%,#22c55e,#4ade80 75%,transparent);
                   box-shadow: 0 0 8px 2px rgba(74,222,128,.45);"
        ></div>

        {{-- Corner brackets --}}
        <template x-if="!isLoading && !scanSuccess">
            <div class="absolute pointer-events-none sc-corner" style="inset: 9% 6% 19% 6%;">
                <div class="absolute top-0 left-0 w-6 h-6 border-t-[3px] border-l-[3px] border-green-400 rounded-tl-lg"></div>
                <div class="absolute top-0 right-0 w-6 h-6 border-t-[3px] border-r-[3px] border-green-400 rounded-tr-lg"></div>
                <div class="absolute bottom-0 left-0 w-6 h-6 border-b-[3px] border-l-[3px] border-green-400 rounded-bl-lg"></div>
                <div class="absolute bottom-0 right-0 w-6 h-6 border-b-[3px] border-r-[3px] border-green-400 rounded-br-lg"></div>
                <div class="absolute inset-x-0 flex justify-center -bottom-6">
                    <span class="text-[11px] font-medium text-green-300/70 tracking-wide select-none">
                        Posisikan barcode dalam kotak
                    </span>
                </div>
            </div>
        </template>

        {{-- ── ZOOM CONTROLS ── --}}
        <div
            x-show="!isLoading && !scanSuccess"
            class="absolute bottom-0 left-0 right-0 z-20 px-4 pt-8 pb-3"
            style="background: linear-gradient(to top, rgba(0,0,0,.8) 0%, transparent 100%);"
        >
            <div class="flex items-center gap-3">

                {{-- Zoom out --}}
                <button
                    type="button"
                    x-on:click="zoomOut()"
                    class="flex items-center justify-center flex-shrink-0 text-white transition-colors rounded-full w-7 h-7 bg-white/15 hover:bg-white/30"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/>
                        <path fill-rule="evenodd" d="M6.25 9a.75.75 0 01.75-.75h4a.75.75 0 010 1.5H7a.75.75 0 01-.75-.75z" clip-rule="evenodd"/>
                    </svg>
                </button>

                {{-- Slider --}}
                <input
                    type="range"
                    class="flex-1 zoom-slider"
                    :min="zoomMin"
                    :max="zoomMax"
                    :step="zoomStep"
                    :value="zoomLevel"
                    x-on:input="applyZoom($event.target.value)"
                    :style="`background: linear-gradient(to right, #4ade80 ${zoomPercent}%, rgba(255,255,255,0.25) ${zoomPercent}%)`"
                />

                {{-- Zoom in --}}
                <button
                    type="button"
                    x-on:click="zoomIn()"
                    class="flex items-center justify-center flex-shrink-0 text-white transition-colors rounded-full w-7 h-7 bg-white/15 hover:bg-white/30"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/>
                        <path fill-rule="evenodd" d="M9 6.25a.75.75 0 01.75.75v2.25H12a.75.75 0 010 1.5H9.75V13a.75.75 0 01-1.5 0v-2.25H6a.75.75 0 010-1.5h2.25V7A.75.75 0 019 6.25z" clip-rule="evenodd"/>
                    </svg>
                </button>

                {{-- Zoom label --}}
                <span
                    class="flex-shrink-0 min-w-[30px] text-right text-[11px] font-mono font-medium text-green-300"
                    x-text="zoomLabel"
                ></span>

                {{-- Tombol tutup --}}
                <button
                    type="button"
                    x-on:click="stopScan()"
                    class="flex-shrink-0 flex items-center gap-1 px-2.5 py-1 ml-1 rounded-full
                           bg-red-600/80 hover:bg-red-500 text-white text-[11px] font-semibold transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2.5" stroke="currentColor" class="w-3 h-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Tutup
                </button>
            </div>

            {{-- Badge mode zoom --}}
            <div class="flex justify-center mt-1.5">
                <span
                    class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                    :class="hwZoomSupport ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300'"
                    x-text="hwZoomSupport ? 'Hardware zoom' : 'Software zoom'"
                ></span>
            </div>
        </div>

        {{-- Loading overlay --}}
        <div
            x-show="isLoading"
            class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-black/85"
        >
            <svg class="mb-3 text-white animate-spin h-9 w-9" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291
                         A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <span class="text-sm tracking-wide text-white animate-pulse">Menyiapkan kamera…</span>
        </div>

        {{-- Sukses overlay --}}
        <div
            x-show="scanSuccess"
            class="absolute inset-0 z-20 flex flex-col items-center justify-center"
            style="background: rgba(0,0,0,.72);"
        >
            <div class="relative flex items-center justify-center mb-3">
                <div class="absolute w-20 h-20 border-4 border-green-400 rounded-full sc-ping"></div>
                <div class="flex items-center justify-center w-16 h-16 bg-green-500 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="3" stroke="white" class="w-9 h-9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
            </div>
            <span class="text-base font-bold text-white">SN Terbaca!</span>
            <span class="text-green-300 text-xs mt-1 font-mono max-w-[80%] truncate" x-text="lastResult"></span>
            <span class="text-gray-400 text-[11px] mt-2">Menyimpan &amp; lanjut…</span>
        </div>

    </div>

</div>
</x-dynamic-component>