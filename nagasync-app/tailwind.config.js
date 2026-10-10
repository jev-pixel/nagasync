import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

// Civic blue, taken from the reference screens: a confident royal blue for
// actions, a deep navy for structure, and a pale blue-gray canvas.
const brand = {
    50: '#eff3ff',
    100: '#dde6ff',
    200: '#bccdff',
    300: '#8fa8ff',
    400: '#6479ff',
    500: '#3f4ff0',
    600: '#2f38d6',
    700: '#262cac',
    800: '#1d2285',
    900: '#141a5c',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand,
                // Existing pages use indigo-*. Aliasing it to the brand scale
                // re-themes every current button, link and focus ring at once.
                indigo: brand,
                surface: '#f3f6fc',
                accent: { 400: '#ffb340', 500: '#ff9f0a', 600: '#e68a00' },
            },
            borderRadius: {
                card: '1.25rem',
                tile: '0.875rem',
            },
            boxShadow: {
                card: '0 8px 24px -10px rgb(20 26 92 / 0.16), 0 2px 6px -2px rgb(20 26 92 / 0.06)',
            },
            keyframes: {
                'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
                'tile-in': {
                    from: { opacity: '0', transform: 'translateY(10px)' },
                    to: { opacity: '1', transform: 'none' },
                },
            },
            animation: {
                'fade-in': 'fade-in 0.18s ease-out both',
                'tile-in': 'tile-in 0.35s ease-out both',
            },
        },
    },

    plugins: [forms],
};