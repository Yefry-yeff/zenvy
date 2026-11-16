import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        'x-cloak',
        'verde',
        'azul',
        'oscuro',
        'bg-emerald-600/80',
        'bg-blue-600/80',
        'bg-gray-900/80',
        'bg-emerald-800/30',
        'bg-blue-800/30',
        'bg-gray-800/50',
        'bg-slate-700/30',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                verde: '#047857',
                azul: '#1D4ED8',
                oscuro: '#1F2937',
            },
            animation: {
                'pulse-slow': 'pulseSlow 10s ease-in-out infinite',
                'float-slow': 'floatSlow 20s ease-in-out infinite',
            },
            keyframes: {
                pulseSlow: {
                    '0%, 100%': { transform: 'scale(1)' },
                    '50%': { transform: 'scale(1.05)' },
                },
                floatSlow: {
                    '0%': { transform: 'translateY(0px) translateX(0px)' },
                    '50%': { transform: 'translateY(-20px) translateX(10px)' },
                    '100%': { transform: 'translateY(0px) translateX(0px)' },
                },
            },
        },
    },
    plugins: [forms],
};
