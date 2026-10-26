import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ command }) => ({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/panel.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    // Tijdens `npm run dev` serveert de devserver ook `public/`, anders vinden de
    // absolute font-URL's uit fonts.css (`/fonts/...`) niets en valt de browser terug
    // op Georgia/Arial. Bij de build blijft dit uit, zodat `public/` niet naar
    // `public/build` wordt gekopieerd.
    publicDir: command === 'serve' ? 'public' : false,
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
}));
