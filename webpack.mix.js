/* eslint-disable import/no-extraneous-dependencies */
// noinspection JSUnresolvedFunction

const mix = require('laravel-mix');
require('lmvh');
require('laravel-mix-serve');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */
mix.disableSuccessNotifications();

mix.js('resources/js/*.js', 'public/js')
  .sass('resources/scss/app.scss', 'public/css', {
    sassOptions: {
      includePaths: ['./node_modules'],
    },
  });

if (mix.inProduction()) {
  mix.versionHash();
} else {
  // noinspection ChainedFunctionCallJS
  mix.webpackConfig({
    devtool: 'source-map',
    resolve: {
      modules: ['./node_modules'],
    },
  }).sourceMaps().serve().browserSync('localhost:8000');
}
