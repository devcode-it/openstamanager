import '../scss/app.scss';
import '@mdi/font/scss/materialdesignicons.scss';

import {InertiaProgress} from '@inertiajs/progress';
import {createInertiaApp} from '@maicol07/inertia-mithril';
import $ from 'cash-dom';
import m from 'mithril';
// noinspection SpellCheckingInspection
import redaxios from 'redaxios';

import {__} from './utils';

// Variabili globali
window.$ = $;
window.m = m;
window.__ = __;

InertiaProgress.init();

// noinspection JSIgnoredPromiseFromCall
createInertiaApp({
  title: title => `${title} - OpenSTAManager`,
  resolve: async (name) => {
    const split = name.split('::');

    if (split.length === 1) {
      // Load bundled page
      const {default: page} = await import(`./Views/${name}.jsx`);
      return page;
    }

    // Load page from module
    const [modulePath, page] = split;

    // noinspection JSUnresolvedVariable
    const osmModule = await import(
      /* @vite-ignore */
      `${window.import_path}/vendor/${modulePath}/index.js`
    );

    return osmModule[page];
  },
  setup({
    el,
    app
  }) {
    m.mount(el, app);
    import('./_material');
  }
});

$('#logout-button').on('click', async () => {
  await redaxios.post(window.route('auth.logout'));
  window.location.href = window.route('auth.login');
});
