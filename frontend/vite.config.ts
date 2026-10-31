import path from 'node:path';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

// En desarrollo, /api se reenvía al servidor PHP (php -S 127.0.0.1:8000 -t ../public ../public/index.php).
const api = process.env.API_URL ?? 'http://127.0.0.1:8000';

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: { alias: { '@': path.resolve(import.meta.dirname, 'src') } },
  server: {
    port: 5173,
    proxy: {
      '/api': { target: api, changeOrigin: true },
      // Fotos de perfil que guarda la API en public/uploads.
      '/uploads': { target: api, changeOrigin: true },
    },
  },
});
