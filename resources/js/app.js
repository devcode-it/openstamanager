import '../scss/app.scss';
import './_material';
import '@mdi/font/scss/materialdesignicons.scss';

import {InertiaProgress} from '@inertiajs/progress';
import {createInertiaApp} from '@maicol07/inertia-mithril';
import {
  WAIT_FOREVER,
  waitUntil
} from 'async-wait-until';
import $ from 'cash-dom';
import m from 'mithril';

import {__} from './utils';

// Fix Mithril JSX durante la compilazione
m.Fragment = '[';

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
      return (await import(`./Views/${name}.jsx`)).default;
    }

    const [, page] = split;
    // noinspection JSUnresolvedVariable
    await waitUntil(() => typeof window.extmodule !== 'undefined', {
      timeout: WAIT_FOREVER
    });
    // noinspection JSUnresolvedVariable
    return window.extmodule[page];
  },
  setup({
    el,
    app
  }) {
    m.mount(el, app);
  }
});
