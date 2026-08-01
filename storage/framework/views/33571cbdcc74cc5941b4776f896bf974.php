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
        <h4 class="mb-2 text-sm font-semibold text-black dark:text-white">Dashboard URL</h4>
        <div class="flex items-center gap-3">
            <input type="text"
                class="flex-1 px-3 py-2.5 border-0 text-sm text-black rounded-2xl dark:text-white ring-1 ring-border-light dark:ring-border-dark bg-secondary-light dark:bg-secondary-dark"
                value="<?php echo e($dashboardUrl); ?>" readonly>

            <button type="button" @click="copyToClipboard('<?php echo e($dashboardUrl); ?>', 'URL Dashboard berhasil disalin!')"
                class="px-4 py-2.5 text-sm text-white rounded-full font-medium bg-main-primary hover:bg-main-primary/90">
                Salin
            </button>
        </div>
    </div>

    <div>
        <h4 class="mb-2 text-sm font-semibold text-black dark:text-white">Password</h4>
        <div class="flex items-center gap-3">
            <input type="text"
                class="flex-1 px-3 py-2.5 border-0 text-sm text-black rounded-2xl dark:text-white ring-1 ring-border-light dark:ring-border-dark bg-secondary-light dark:bg-secondary-dark"
                value="<?php echo e($password); ?>" readonly>

            <button type="button" @click="copyToClipboard('<?php echo e($password); ?>', 'Password berhasil disalin!')"
                class="px-4 py-2.5 text-sm text-white rounded-full font-medium bg-main-primary hover:bg-main-primary/90">
                Salin
            </button>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($lastAccessed): ?>
        <div>
            <h4 class="mb-2 text-sm font-semibold text-black dark:text-white">Terakhir Diakses</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo e($lastAccessed); ?></p>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div>
        <h4 class="mb-2 text-sm font-semibold text-black dark:text-white">Status</h4>
        <div
            class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 capitalize w-fit <?php echo e($isActive ? 'fi-color-success bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30 fi-color-success' : 'fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30 fi-color-danger'); ?>">
            <?php echo e($isActive ? 'Active' : 'Inactive'); ?>

        </div>
    </div>

    <div class="p-6 rounded-2xl bg-secondary-light dark:bg-secondary-dark ring-1 ring-border-light dark:ring-border-dark">
        <h5 class="mb-2 text-sm font-medium text-black dark:text-white">Petunjuk</h5>
        <ol class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <li>1. Bagikan URL Dashboard ke user eksternal</li>
            <li>2. Berikan password untuk mengakses</li>
            <li>3. User bisa melihat progress project dan ticket</li>
        </ol>
    </div>

    <div x-show="notification.show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-full"
        class="fixed z-50 px-4 py-3 text-blue-600 bg-blue-100 shadow-lg rounded-2xl top-4 right-4"
        style="display: none;">

        <span class="text-sm font-medium" x-text="notification.message"></span>
    </div>

</div><?php /**PATH /var/www/erp-app-main/resources/views/filament/components/external-access-modal.blade.php ENDPATH**/ ?>