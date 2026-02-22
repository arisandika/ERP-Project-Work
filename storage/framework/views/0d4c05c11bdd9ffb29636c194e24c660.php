<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Client Project Dashboard'); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Livewire Styles -->
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>


    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="px-4 antialiased transition-colors duration-300 bg-gray-50 text-gray-950 dark:bg-gray-950 dark:text-white md:px-0"
    x-data="{
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

    <div x-data="{
        notifications:[],
        add(e) {
            const id = Date.now();
            this.notifications.push({
                id: id,
                message: e.detail.message,
                type: e.detail.type || 'info'
            });

            setTimeout(() => { this.remove(id) }, 4000);
        },
        remove(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }" @notify.window="add($event)" class="fixed z-10 flex flex-col items-end gap-3 pointer-events-none top-4 right-4">

        <template x-for="notif in notifications" :key="notif.id">
            <div x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-300 transform"
                x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-full"
                :class="{
                    'text-green-600 bg-green-100': notif.type === 'success',
                    'text-red-600 bg-red-100': notif.type === 'error',
                    'text-blue-600 bg-blue-100': notif.type === 'info'
                }"
                class="flex items-center justify-between max-w-sm gap-4 px-4 py-3 font-medium rounded-lg shadow-lg pointer-events-auto min-w-">
                <div class="flex items-center gap-2">
                    <svg x-show="notif.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg x-show="notif.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>

                    <span class="text-sm break-words" x-text="notif.message"></span>
                </div>

                <button @click="remove(notif.id)" type="button"
                    class="flex-shrink-0 transition-opacity text-black/80 opacity-80 hover:opacity-100 focus:outline-none"
                    title="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <?php echo e($slot); ?>


    <!-- Livewire Scripts -->
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>


    <!-- Additional Scripts -->
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html><?php /**PATH C:\laragon\www\erp-app\resources\views/layouts/external.blade.php ENDPATH**/ ?>