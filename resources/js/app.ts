/* eslint-disable no-var,vars-on-top */
// noinspection ES6ConvertVarToLetConst

import '../scss/app.scss';
import '@mdi/font/scss/materialdesignicons.scss';

import {InertiaProgress} from '@inertiajs/progress';
import {createInertiaApp} from '@maicol07/inertia-mithril';
import cash from 'cash-dom';
import Mithril from 'mithril';
// noinspection SpellCheckingInspection
import redaxios from 'redaxios';
import {registerSW} from 'virtual:pwa-register';
import type router from 'ziggy-js';

import {type Page} from './Components';
import {
  __ as translator,
  showSnackbar
} from './utils';

// Variabili globali
declare global {
  const importPath: string;
  const translations: {[key: string]: string};
  const route: typeof router;

  var $: typeof cash;
  var m: typeof Mithril;
  // eslint-disable-next-line @typescript-eslint/naming-convention
  var __: typeof translator;
}
globalThis.$ = cash;
globalThis.m = Mithril;
globalThis.__ = translator;

InertiaProgress.init();

await createInertiaApp({
  title: ((title) => `${title} - OpenSTAManager`),
  resolve: async (name: string) => {
    const split = name.split('::');

    if (split.length === 1) {
      // Load bundled page
      // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
      const {default: page}: {default: Page} = await import(`./Views/${name}.tsx`);
      return page;
    }

    // Load page from module
    const [modulePath, page] = split;

    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
    const osmModule: {default: unknown, [key: string]: unknown} = await import(
      /* @vite-ignore */
      `${importPath}/vendor/${modulePath}/index.js`
    );

    return osmModule[page];
  },
  setup({el, app}: {el: Element, app: Mithril.ComponentTypes}) {
    m.mount(el, app);
    import('./_material');
  }
});

$('#logout-button')
  .on('click', async () => {
    await redaxios.post(route('auth.logout'));
    window.location.href = route('auth.login');
  });

// eslint-disable-next-line @typescript-eslint/no-unsafe-call
const updateSW = registerSW({
  async onNeedRefresh() {
    const action = await showSnackbar(__('Aggiornamento del frontend disponibile!'), false, __('Ricarica'), __('Annulla'));
    if (action === 'action') {
      await updateSW();
    }
  },
  onOfflineReady() {
    // eslint-disable-next-line no-void
    void showSnackbar(__('È ora possibile lavorare offline!'), false);
  }
}) as (reloadPage?: boolean) => Promise<void>;
