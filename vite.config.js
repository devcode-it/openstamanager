import osmConfig from '@openstamanager/vite-config';
import {defineConfig} from 'laravel-vite';
import livereload from 'rollup-plugin-livereload';

export default defineConfig(osmConfig({
  plugins: [
    livereload('public/build')
  ]
}));
