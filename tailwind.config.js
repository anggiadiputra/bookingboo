import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Models/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Whitney SSm"', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: '#E11D48',
                    hover: '#BE123C',
                    light: '#FFE4E6',
                    soft: '#FFF1F2',
                },
                medical: {
                    teal: '#0D9488',
                    sky: '#0284C7',
                    navy: '#0F172A',
                },
                verified: '#10B981',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            boxShadow: {
                'soft': '0 2px 10px 0 rgba(0, 0, 0, 0.04), 0 1px 3px 0 rgba(0, 0, 0, 0.02)',
                'card': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                'nav': '0 -4px 16px -1px rgba(0, 0, 0, 0.06)',
            },
        },
    },

    plugins: [forms],
};
