/* eslint-disable import/no-extraneous-dependencies */
import Inertia from 'inertia-plugin/vite';
import laravel from 'laravel-vite-plugin';
import * as fs from 'node:fs';
import * as path from 'node:path';
import {defineConfig} from 'vite';
import laravelTranslations from 'vite-plugin-laravel-translations';
import progress from 'vite-plugin-progress';
import {VitePWA} from 'vite-plugin-pwa';
import installedPackages from './vendor/composer/installed.json';

const modules = installedPackages.packages.filter((packageInfo) => packageInfo.type === 'openstamanager-module');
const bootstrapFiles = [];
for (const module of modules) {
  const basePath = `./vendor/${module.name}/resources/ts/bootstrap`;
  if (fs.existsSync(`${basePath}.ts`)) {
    bootstrapFiles.push(`${basePath}.ts`);
  } else if (fs.existsSync(`${basePath}.tsx`)) {
    bootstrapFiles.push(`${basePath}.tsx`);
  }
}


// noinspection JSUnusedGlobalSymbols
export default defineConfig({
  assetsInclude: '**/*.xml',
  build: {
    minify: false,
    target: 'esnext'
  },
  resolve: {
    alias: {
      '~': '/resources/ts',
      '@osm': '/resources/ts'
    }
  },
  esbuild: {
    jsx: 'transform',
    jsxFactory: 'm',
    jsxFragment: '\'[\'',
    jsxInject: 'import m from \'mithril\''
  },
  plugins: [
    laravel({
      input: [
        'resources/ts/app.ts',
        ...bootstrapFiles
      ],
      refresh: true
    }),
    laravelTranslations({
      namespace: 'osm',
      includeJson: true
    }),
    // eslint-disable-next-line new-cap
    Inertia({
      namespaces: ({npm, composer}) => {
        const namespaces = [];
        for (const module of modules) {
          // @ts-ignore
          namespaces.push(composer(module.name));
        }
        return namespaces;
      }
    }),
    progress({
      // eslint-disable-next-line unicorn/prefer-module
      srcDir: path.resolve(__dirname, 'resources/ts')
    }),
    // eslint-disable-next-line new-cap
    VitePWA({
      // TODO: Check options
      includeAssets: [
        'resources/images/favicon/favicon.ico',
        '../robots.txt',
        'resources/images/favicon/apple-touch-icon.png',
        'resources/images/*.png'
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
            type: 'image/png'
          },
          {
            src: 'android-chrome-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: 'android-chrome-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
      useCredentials: true
    })
  ]
});
