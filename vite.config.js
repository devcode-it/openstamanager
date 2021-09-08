import osmConfig from '@openstamanager/vite-config';
import { defineConfig } from 'laravel-vite';
// import ViteFonts from 'vite-plugin-fonts';

export default defineConfig(osmConfig({
  /* NOT WORKING: waiting a fix
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: '$mdi-font-path: "./build";',
      },
    },
  }, */
  /* NOT WORKING. Waiting a fix
    ViteFonts({
      google: {
        families: ['Montserrat', 'Nunito']
      }
    }) */
}));
