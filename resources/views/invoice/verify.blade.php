<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Invoice - {{ config('app.name') }}</title>

    <link rel="icon" type="image/x-icon" href="https://www.nexicon.id/favicon.ico">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

        /* Hide number spinner */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>

<body class="px-4 antialiased transition-colors duration-300 bg-gray-50 text-gray-950 dark:bg-gray-950 dark:text-white md:px-0">

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
    }" class="flex flex-col justify-center min-h-screen py-12 sm:px-6 lg:px-8">

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
                            Verifikasi Dokumen
                        </h1>
                        <p
                            class="mt-2 text-sm text-center text-gray-500 fi-simple-header-subheading dark:text-gray-400">
                            Demi keamanan, verifikasi identitas Anda untuk melanjutkan
                        </p>
                    </header>

                    <div class="grid fi-form gap-y-6">

                        <!-- Info Invoice Card -->
                        <div
                            class="p-4 text-center rounded-lg bg-blue-50 dark:bg-blue-900/20 ring-1 ring-blue-600/40 dark:ring-blue-400/20">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span
                                    class="text-xs font-medium tracking-wide text-blue-600 uppercase dark:text-blue-400">Nomor
                                    Invoice</span>
                                <div class="text-base font-bold text-gray-900 dark:text-white">{{ $invoiceNumber }}
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('invoice.verify.submit', ['number' => $invoiceNumber]) }}"
                            class="grid fi-form gap-y-6">
                            @csrf

                            <!-- Input 4 Digit -->
                            <div class="fi-fo-field-wrp">

                                <div class="grid gap-y-2">

                                    <label for="phone_last_4"
                                        class="inline-flex items-center mx-auto fi-fo-field-wrp-label gap-x-3">
                                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                            Masukkan <span class="font-bold text-blue-600 dark:text-blue-400">4 digit
                                                terakhir</span> No. HP
                                            Anda
                                        </span>
                                    </label>

                                    <div class="grid auto-cols-fr gap-y-2">
                                        <div
                                            class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-blue-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-blue-500 fi-fo-text-input overflow-hidden">
                                            <div class="flex-1 min-w-0 fi-input-wrp-input">
                                                <input type="text" id="phone_last_4" name="phone_last_4"
                                                    value="{{ old('phone_last_4') }}" required maxlength="4"
                                                    inputmode="numeric" pattern="\d{4}"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                    class="fi-input block text-center tracking-[0.5em] font-bold w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3"
                                                    placeholder="••••" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @error('phone_last_4')
                                    <p class="mt-2 text-sm text-center text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <button type="submit"
                                    class="w-full fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-blue fi-btn-color-blue fi-color-blue fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-blue-600 text-white hover:bg-blue-500 focus-visible:ring-blue-500/50 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus-visible:ring-blue-400/50 fi-ac-action fi-ac-btn-action">
                                    Buka Dokumen
                                </button>
                            </div>
                        </form>

                    </div>

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
    </div>
</body>

</html>