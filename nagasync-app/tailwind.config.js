import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

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
            ns: {
                navy: '#2a1f6e', canvas: '#f5f6fb', ink: '#1e1b3a', mist: '#eceffa',
                violet: '#5b4bdb', orange: '#ffa51f', coral: '#e8445a', teal: '#3ccfe0',
            },
        },
        boxShadow: {
            card: '0 1px 2px rgba(30,27,58,.04), 0 8px 24px rgba(30,27,58,.06)',
        },
        },
    },

    plugins: [forms],
};
