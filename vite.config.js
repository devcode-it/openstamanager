import { esbuildFlowPlugin, flowPlugin } from '@bunchtogether/vite-plugin-flow';
import { defineConfig } from 'laravel-vite';
// import ViteFonts from 'vite-plugin-fonts';

export default defineConfig({
  assetsInclude: ['js', 'png'],
  /* NOT WORKING: waiting a fix
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: '$mdi-font-path: "./build";',
      },
    },
  }, */
  build: {
    minify: false,
    rollupOptions: {
      manualChunks: {},
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]'
      },
      preserveEntrySignatures: 'allow-extension'
    }
  },
  esbuild: {
    jsxFactory: 'm',
    jsxFragment: 'm.Fragment'
  },
  optimizeDeps: {
    esbuildOptions: {
      plugins: [esbuildFlowPlugin(/\.(flow|jsx?)$/, (path) => (/\.jsx$/.test(path) ? 'jsx' : 'js'), {
        all: true,
        pretty: true,
        ignoreUninitializedFields: false,
      })]
    },
  },
  plugins: [
    flowPlugin({
      include: /\.(flow|jsx?)$/,
      exclude: /node_modules/,
      flow: {
        all: true,
        pretty: true,
        ignoreUninitializedFields: false,
      }
    })
    /* NOT WORKING. Waiting a fix
    ViteFonts({
      google: {
        families: ['Montserrat', 'Nunito']
      }
    }) */
  ]
});
