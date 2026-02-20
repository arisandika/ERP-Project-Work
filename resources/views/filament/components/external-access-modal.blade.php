<div class="space-y-4" x-data="{
    notification: { show: false, message: '' },
    
    copyToClipboard(text, message) {
        // Cara Modern (Navigator API)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                this.showNotification(message);
            });
        } else {
            // Fallback untuk browser lama / HTTP
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed'; // Hindari scrolling
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                this.showNotification(message);
            } catch (err) {
                console.error('Gagal menyalin', err);
            }
            document.body.removeChild(textArea);
        }
    },

    showNotification(msg) {
        this.notification.message = msg;
        this.notification.show = true;
        setTimeout(() => { this.notification.show = false }, 3000);
    }
}">

    <div>
        <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Dashboard URL</h4>
        <div class="flex items-center space-x-2">
            <input type="text"
                class="flex-1 px-3 py-2 text-sm text-gray-900 border border-gray-300 rounded-md dark:border-gray-600 bg-gray-50 dark:bg-gray-700 dark:text-gray-100"
                value="{{ $dashboardUrl }}" readonly>

            <button type="button" @click="copyToClipboard('{{ $dashboardUrl }}', 'URL Dashboard berhasil disalin!')"
                class="px-3 py-2 text-sm text-white bg-blue-500 rounded-md hover:bg-blue-600">
                Salin
            </button>
        </div>
    </div>

    <div>
        <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Password</h4>
        <div class="flex items-center space-x-2">
            <input type="text"
                class="flex-1 px-3 py-2 font-mono text-sm text-gray-900 border border-gray-300 rounded-md dark:border-gray-600 bg-gray-50 dark:bg-gray-700 dark:text-gray-100"
                value="{{ $password }}" readonly>

            <button type="button" @click="copyToClipboard('{{ $password }}', 'Password berhasil disalin!')"
                class="px-3 py-2 text-sm text-white bg-blue-500 rounded-md hover:bg-blue-600">
                Salin
            </button>
        </div>
    </div>

    @if($lastAccessed)
        <div>
            <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Terakhir Diakses</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $lastAccessed }}</p>
        </div>
    @endif

    <div>
        <h4 class="mb-2 font-semibold text-gray-900 dark:text-gray-100">Status</h4>
        <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isActive ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
            {{ $isActive ? 'Active' : 'Inactive' }}
        </span>
    </div>

    <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
        <h5 class="mb-2 font-medium text-blue-900 dark:text-blue-100">Petunjuk</h5>
        <ol class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
            <li>1. Bagikan URL Dashboard ke user eksternal</li>
            <li>2. Berikan password untuk mengakses</li>
            <li>3. User bisa melihat progress project dan ticket</li>
        </ol>
    </div>

    <div x-show="notification.show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-full"
        class="fixed z-50 px-4 py-3 text-blue-600 bg-blue-100 rounded-md shadow-lg top-4 right-4"
        style="display: none;">

        <span class="text-sm font-medium" x-text="notification.message"></span>
    </div>
</div>