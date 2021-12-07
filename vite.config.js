import osmConfig from '@openstamanager/vite-config';
import autoprefixer from 'autoprefixer';
import {defineConfig} from 'laravel-vite';

export default defineConfig(osmConfig({
  css: {
    postcss: {
      plugins: [autoprefixer()]
    }
  }
}));
