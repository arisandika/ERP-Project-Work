<div class="flex flex-col justify-center min-h-screen py-12 sm:px-6 lg:px-8">

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

    <main
        class="w-full max-w-lg px-6 py-12 mx-auto my-16 bg-white shadow-sm fi-simple-main ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 rounded-xl sm:px-12">

        <div class="fi-simple-page">

            <section class="grid auto-cols-fr gap-y-8">
                <header class="flex flex-col items-center fi-simple-header">
                    <div style="height: 1.5rem;" class="flex mb-8 fi-logo">
                        <div class="flex items-center">
                            <img src="https://erp.arihub.my.id/assets/logo.png" alt="Logo" class="h-11">
                        </div>
                    </div>
                    <h1
                        class="text-2xl font-bold tracking-tight text-center fi-simple-header-heading text-gray-950 dark:text-white">
                        Client Project Dashboard
                    </h1>
                    <p class="mt-2 text-sm text-center text-gray-500 fi-simple-header-subheading dark:text-gray-400">
                        Masuk untuk melihat progress project Anda
                    </p>
                </header>

                <form wire:submit.prevent="authenticate" class="grid fi-form gap-y-6" id="form">

                    <!-- Input Field Wrapper -->
                    <div class="fi-fo-field-wrp">
                        <div class="grid gap-y-2">

                            <label for="password" class="inline-flex items-center gap-x-3 fi-fo-field-wrp-label">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                    Access Password
                                    <sup class="font-medium text-red-600 dark:text-red-400">*</sup>
                                </span>
                            </label>

                            <div class="grid auto-cols-fr gap-y-2">
                                <div
                                    class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-blue-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-blue-500 fi-fo-text-input overflow-hidden">
                                    <div class="flex-1 min-w-0 fi-input-wrp-input">
                                        <input
                                            class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3"
                                            placeholder="Enter your access password" type="password" id="password"
                                            wire:model="password" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($error): ?>
                        <div
                            class="p-4 text-sm text-center text-red-700 border border-red-200 rounded-lg bg-red-50 dark:bg-red-900/30 dark:text-red-400 dark:border-red-900/50">
                            <?php echo e($error); ?>

                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <!-- Submit Button -->
                    <div class="fi-form-actions">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-blue fi-btn-color-blue fi-color-blue fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-blue-600 text-white hover:bg-blue-500 focus-visible:ring-blue-500/50 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus-visible:ring-blue-400/50 fi-ac-action fi-ac-btn-action"
                            wire:loading.class="opacity-50 cursor-not-allowed">

                            <span wire:loading.remove>Sign in</span>

                            <div wire:loading class="flex items-center w-full gap-2">
                                <svg class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>

                        </button>
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

        </div>

    </main>
</div><?php /**PATH C:\laragon\www\erp-app\resources\views/livewire/external-login.blade.php ENDPATH**/ ?>