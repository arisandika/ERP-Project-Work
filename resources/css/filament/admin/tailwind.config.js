import preset from "../../../../vendor/filament/filament/tailwind.config.preset";

export default {
    darkMode: "class",
    presets: [preset],
    content: [
        "./app/Filament/**/*.php",
        "./resources/views/filament/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
    ],
    theme: {
        extend: {
            colors: {
                "main-primary": "#1c9cf0",
                "main-secondary": "#1d91de",

                "main-light": "#ffffff",
                "main-dark": "#000", // 'main-dark': '#1c2433',

                "secondary-light": "#f7f8f8",
                "secondary-dark": "#17181c", // 'secondary-dark': '#2a303f',

                "accent-light": "#e5e5e6",
                "accent-dark": "#232428", // 'accent-dark': '#2a3656',

                "border-light": "#d9dbdc",
                "border-dark": "#454649", // 'border-dark': '#3d4354',

                "main-accent": "#9da1a640",
                "secondary-accent": "#334c82",
            },
        },
    },
};
