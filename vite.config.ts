/* eslint-disable import/no-extraneous-dependencies,new-cap */
import {resolve} from 'node:path';

import FastGlob from 'fast-glob';
import {readJSON} from 'fs-extra';
import Inertia from 'inertia-plugin/vite';
import laravel from 'laravel-vite-plugin';
import type {TsConfigJson} from 'type-fest';
import {
  AliasOptions,
  defineConfig
} from 'vite';
import laravelTranslations from 'vite-plugin-laravel-translations';
// import progress from 'vite-plugin-progress';
import {VitePWA} from 'vite-plugin-pwa';

import installedPackages from './vendor/composer/installed.json';

const modules = installedPackages.packages.filter((packageInfo) => packageInfo.type === 'openstamanager-module');

// noinspection JSUnusedGlobalSymbols
export default defineConfig(async () => {
  const bootstrapFiles = await FastGlob('./vendor/*/*/resources/ts/bootstrap.{tsx,ts,jsx,js}');

  // Load module aliases from tsconfig.json
  const aliases: AliasOptions = {
    '@osm': '/resources/ts'
  };

  const mods = modules.map(async (module) => {
    const modulePath = `./vendor/composer/${module['install-path']!}`;
    return {
      modulePath,
      tsconfig: await readJSON(`${modulePath}/tsconfig.json`, 'utf8') as TsConfigJson
    };
  });
  for await (const module of mods) {
    const paths = module.tsconfig.compilerOptions?.paths;
    if (paths) {
      for (const [alias, path] of Object.entries(paths)) {
        if (alias !== '@osm/*') {
          aliases[alias.replace('/*', '')] = resolve(`${module.modulePath}/${path[0]}`);
        }
      }
    }
  }

  return {
    assetsInclude: '**/*.xml',
    build: {
      sourcemap: true,
      target: 'esnext'
    },
    resolve: {
      alias: aliases
    },
    esbuild: {
      jsx: 'transform',
      jsxFactory: 'm',
      jsxFragment: '\'[\'',
      jsxInject: 'import m from \'mithril\'',
      minifyIdentifiers: false
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
        namespace: false,
        includeJson: true
      }),
      Inertia({
        namespaces: ({npm, composer}) => modules.map(
          // @ts-expect-error - Inertia plugins typings are not updated (dir parameter is 'vendor' by default)
          (module) => composer(module.name)
        ),
        extensions: ['tsx', 'ts', 'jsx', 'js']
      }),
      // progress({
      //   // eslint-disable-next-line unicorn/prefer-module
      //   srcDir: path.resolve(__dirname, 'resources/ts'),
      // }),
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
  };
});
