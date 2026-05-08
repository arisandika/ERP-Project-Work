<?php if (isset($component)) { $__componentOriginal511d4862ff04963c3c16115c05a86a9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal511d4862ff04963c3c16115c05a86a9d = $attributes; } ?>
<?php $component = Illuminate\View\DynamicComponent::resolve(['component' => $getFieldWrapperView()] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dynamic-component'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\DynamicComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['field' => $field]); ?>
    <div x-data="{
            state: $wire.$entangle('<?php echo e($getStatePath()); ?>').live,
            isScanning: false,
            isLoading: false,
            html5QrCode: null,
            readerId: 'reader-<?php echo e($getId()); ?>',
            lastScanned: null, // Variabel untuk Anti-Spam

            initScanner() {
                if (typeof Html5Qrcode === 'undefined') {
                    let script = document.createElement('script');
                    script.src = 'https://unpkg.com/html5-qrcode';
                    script.onload = () => this.startScan();
                    document.head.appendChild(script);
                } else {
                    this.startScan();
                }
            },

            startScan() {
                this.isScanning = true;
                this.isLoading = true;

                setTimeout(() => {
                    if (!this.html5QrCode) {
                        this.html5QrCode = new Html5Qrcode(this.readerId);
                    }

                    this.html5QrCode.start(
                        { facingMode: 'environment' },
                        {
                            fps: 15,
                            qrbox: { width: 280, height: 120 }
                        },
                        (decodedText) => {
                            // LOGIKA ANTI-SPAM: Cegah pengiriman data beruntun untuk barcode yang sama
                            if (this.lastScanned !== decodedText) {
                                this.state = decodedText;
                                this.lastScanned = decodedText;

                                // Reset anti-spam setelah 1.5 detik
                                setTimeout(() => {
                                    this.lastScanned = null;
                                }, 1500);
                            }
                        },
                        (err) => { /* Abaikan error pencarian fokus */ }
                    ).then(() => {
                        this.isLoading = false;
                    }).catch((err) => {
                        this.isLoading = false;
                        this.isScanning = false;
                        alert('Gagal membuka kamera. Pastikan browser Anda memiliki izin akses kamera.');
                    });
                }, 100);
            },

            stopScan() {
                if(this.html5QrCode && this.html5QrCode.isScanning) {
                    this.html5QrCode.stop().then(() => {
                        this.isScanning = false;
                    }).catch((err) => console.log('Stop error', err));
                } else {
                    this.isScanning = false;
                }
            }
        }"
        class="w-full"
    >

        <button
            type="button"
            x-on:click="initScanner()"
            x-show="!isScanning"
            class="w-full flex flex-col items-center justify-center py-6 px-4 border-2 border-dashed border-primary-400 rounded-xl bg-primary-50 hover:bg-primary-100 transition-all group dark:bg-gray-800 dark:border-primary-500 dark:hover:bg-gray-700"
        >
            <div class="p-3 bg-white dark:bg-gray-700 rounded-full shadow-sm text-primary-600 dark:text-primary-400 mb-3 group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5M19.125 3.75h-4.5c-.621 0-1.125.504-1.125 1.125m-6.75 15.75h-4.5c-.621 0-1.125-.504-1.125-1.125v-4.5m16.5 5.625c0 .621-.504 1.125-1.125 1.125h-4.5m-5.25-10.5v5.25m3-5.25v5.25m3-5.25v5.25M6.75 9v5.25" />
                </svg>
            </div>
            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Klik untuk Buka Scanner</span>
            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">Arahkan kamera ke Barcode / QR Code</span>
        </button>


        <div x-show="isScanning" style="display: none;" class="relative w-full overflow-hidden rounded-xl shadow-lg border border-gray-300 dark:border-gray-700 bg-black">

            <div x-show="isLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-black/80 z-10">
                <svg class="animate-spin h-8 w-8 text-white mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-white text-sm font-medium animate-pulse">Menyiapkan Lensa...</span>
            </div>

            <div x-bind:id="readerId" class="w-full min-h-[250px] flex items-center justify-center"></div>

            <button
                type="button"
                x-on:click="stopScan()"
                class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-20 flex items-center gap-2 bg-red-600/90 backdrop-blur-sm text-white px-5 py-2 rounded-full text-sm font-semibold shadow-lg hover:bg-red-500 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Tutup Kamera
            </button>
        </div>

    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $attributes = $__attributesOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__attributesOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal511d4862ff04963c3c16115c05a86a9d)): ?>
<?php $component = $__componentOriginal511d4862ff04963c3c16115c05a86a9d; ?>
<?php unset($__componentOriginal511d4862ff04963c3c16115c05a86a9d); ?>
<?php endif; ?>
<?php /**PATH D:\Nurul Fauziah\Project Work\Nexicon\ERP-Project-Work\resources\views/filament/forms/components/camera-scanner.blade.php ENDPATH**/ ?>