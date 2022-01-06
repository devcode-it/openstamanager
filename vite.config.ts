/* eslint-disable import/no-extraneous-dependencies */
import osmConfig from '@openstamanager/vite-config';
import {defineConfig} from 'laravel-vite';
import {VitePWA} from 'vite-plugin-pwa';

export default defineConfig(osmConfig({
  plugins: [
    VitePWA({
      includeAssets: [
        'images/favicon/favicon.ico',
        '../robots.txt',
        'images/favicon/apple-touch-icon.png',
        'images/*.png'
      ],
      manifest: {
        name: 'OpenSTAManager',
        short_name: 'OSM',
        description: 'Il software gestionale open source per l\'assistenza tecnica e la fatturazione',
        categories: ['business', 'productivity'],
        display: 'minimal-ui',
        theme_color: '#3f3f3f',
        background_color: '#fffff',
        icons: [
          {
            src: 'android-chrome-192x192.png',
            sizes: '192x192',
            type: 'image/png',
          },
          {
            src: 'android-chrome-512x512.png',
            sizes: '512x512',
            type: 'image/png',
          },
          {
            src: 'android-chrome-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable',
          }
        ]
      },
      useCredentials: true
    })
  ]
}));
