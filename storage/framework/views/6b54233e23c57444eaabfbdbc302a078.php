<div x-data="{
        darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        init() {
            if (this.darkMode) document.documentElement.classList.add('dark');
        }
    }">

    <!-- Theme Toggle -->
    <div class="absolute top-4 right-4">
        <button @click="toggleTheme()"
            class="p-2 text-gray-500 transition-colors duration-200 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700">
            <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
            </svg>
            <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                </path>
            </svg>
        </button>
    </div>

    <section class="grid auto-cols-fr gap-y-8">
        <header class="flex flex-col items-center fi-simple-header">
            <div style="height: 1.5rem;" class="flex mb-8 fi-logo">
                <div class="flex items-center">
                    <img src="https://erp.arihub.my.id/assets/logo.png" alt="Logo" class="h-11">
                </div>
            </div>
            <h1
                class="text-2xl font-bold tracking-tight text-center fi-simple-header-heading text-gray-950 dark:text-white">
                Nexicon ERP Login
            </h1>
            <p class="mt-2 text-sm text-center text-gray-500 fi-simple-header-subheading dark:text-gray-400">
                Selamat datang! Masuk untuk mengelola bisnis Anda dengan mudah dan efisien.
            </p>
        </header>

        <form wire:submit="authenticate" class="space-y-6">

            <?php echo e($this->form); ?>


            <div class="btn-wrapper">
                <?php echo e($this->getFormActions()[0]); ?>

            </div>

        </form>

        <!-- Footer -->
        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-500">
                PT. NEXT GENERATION SOLUTIONS<br>
                <a href="https://www.nexicon.id"
                    class="font-medium transition-colors hover:text-blue-600 dark:hover:text-blue-400">www.nexicon.id</a>
            </p>
        </div>

    </section>
</div><?php /**PATH C:\laragon\www\erp-app\resources\views/filament/pages/auth/login.blade.php ENDPATH**/ ?>