import '~/../scss/app.scss';

import {createInertiaApp} from '@maicol07/inertia-mithril';
import Mithril, {ClassComponent} from 'mithril';
import {registerSW} from 'virtual:pwa-register';

import {showSnackbar} from '~/utils/misc';
import {
  resolvePage,
  resolvePluginPage
} from '~inertia';

import type Page from './Components/Page';
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

// Load modules bootstrap
for (const [name, module] of Object.entries(app.modules)) {
  if (module.hasBootstrap) {
    // eslint-disable-next-line no-await-in-loop
    await import(`./vendor/${name}/resources/ts/bootstrap.tsx`);

    // const path = moduleURL.replace('{{modulePath}}', `${module.moduleVendor}/${name}`);

    // eslint-disable-next-line no-await-in-loop
    // importedModules[name] = await import(/* @vite-ignore */ path) as OpenSTAManager.ImportedModule;
    // importedModules[name].bootstrap?.();
    // window.InertiaPlugin.addNamespace(name, path);
  }
}

await createInertiaApp({
  title: ((title) => `${title} - OpenSTAManager`),
  resolve: async (name) => {
    let page: OpenSTAManager.ImportedModule | ClassComponent = await resolvePluginPage(name);
    if (!page) {
      const bundledPages = import.meta.glob<OpenSTAManager.ImportedModule>('./Views/**/*.tsx');
      page = await bundledPages[`./Views/${name}.tsx`]();
    }
    page = (page as OpenSTAManager.ImportedModule).default || page;
    return page;
  },
  setup({el, App, props}) {
    if (!el) {
      throw new Error('No mounting HTMLElement found');
    }

    m.mount(el, {
      view: (vnode) => m(App, props)
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
