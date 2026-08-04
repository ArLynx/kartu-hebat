import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
                    50: '#eef3fb',
                    100: '#d9e4f6',
                    700: '#1a2b4c',
                    800: '#0d2344',
                    900: '#031636',
                    950: '#021128',
                },
                brand: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#1470e8',
                    600: '#0058bd',
                    700: '#004494',
                },
                surface: '#fbf8fc',
            },
            boxShadow: {
                card: '0 2px 8px rgba(3, 22, 54, 0.05)',
                lift: '0 12px 32px rgba(3, 22, 54, 0.12)',
            },
            borderRadius: {
                '2xl': '1rem',
            },
        },
    },
    plugins: [forms, typography],
};
