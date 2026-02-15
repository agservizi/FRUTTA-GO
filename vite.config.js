import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  root: 'assets',
  build: {
    outDir: '../public/assets',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'assets/js/main.js'),
        css: resolve(__dirname, 'assets/css/main.css')
      }
    }
  },
  server: {
    host: '0.0.0.0',
    port: 3000,
    open: false
  }
});