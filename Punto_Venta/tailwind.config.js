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
  'bg-slate-700/30'
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
        },
    },
    plugins: [forms],
};
