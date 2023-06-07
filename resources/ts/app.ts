import '@osm/../scss/app.scss';

import {createInertiaApp} from '@maicol07/inertia-mithril';
import {showSnackbar} from '@osm/utils/misc';
import Mithril from 'mithril';
import {registerSW} from 'virtual:pwa-register';

import {resolvePage} from '~inertia';

import {OpenSTAManager} from './typings/modules';
import {
  __ as stringTranslator,
  _v as vnodeTranslator,
  tr as translator
} from './utils/i18n';

// Let vite process assets
import.meta.glob([
  '../images/**',
  '../fonts/**'
]);

// Variabili globali
window.m = Mithril;
window.tr = translator;
window._v = vnodeTranslator;
window.__ = stringTranslator;

// Load modules bootstrap file
import.meta.glob('../../vendor/**/**/resources/{js,ts}/bootstrap.{tsx,ts,js,jsx}', {eager: true});

await createInertiaApp({
  title: ((title) => `${title} - OpenSTAManager`),
  // This rule is disabled to avoid a bug in Inertia plugin
  // eslint-disable-next-line arrow-body-style
  resolve: resolvePage(() => {
    return import.meta.glob<OpenSTAManager.ImportedModule>('./Views/**/*.tsx');
  }),
  setup({el, App, props}) {
    if (!el) {
      throw new Error('No mounting HTMLElement found');
    }

    m.mount(el, {
      view: () => m(App, props)
    });
  }
});

// PWA
const updateSW = registerSW({
  async onNeedRefresh() {
    const action = await showSnackbar(__('Aggiornamento del frontend disponibile!'), false, __('Ricarica'), __('Annulla'));
    if (action === 'action') {
      await updateSW();
    }
  },
  onOfflineReady() {
    void showSnackbar(__('È ora possibile lavorare offline!'), false);
  }
}) as (reloadPage?: boolean) => Promise<void>;