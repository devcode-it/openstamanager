import {InertiaProgress} from '@inertiajs/progress';
import {createInertiaApp} from '@maicol07/inertia-mithril';
import cash from 'cash-dom';
import Mithril, {ClassComponent} from 'mithril';
import {registerSW} from 'virtual:pwa-register';

import type Page from './Components/Page';
import {OpenSTAManager} from './typings';
import {
  __ as translator,
  showSnackbar
} from './utils';

// Variabili globali
globalThis.$ = cash;
globalThis.m = Mithril;
globalThis.__ = translator;

InertiaProgress.init();

const importedModules: Record<string, OpenSTAManager.ImportedModule> = {};
const moduleURL = `${window.location.origin}/modules/{{modulePath}}/index.js`;

// Load modules bootstrap
for (const [name, module] of Object.entries(app.modules)) {
  if (module.hasBootstrap) {
    const path = moduleURL.replace('{{modulePath}}', `${module.moduleVendor}/${name}`);

    // eslint-disable-next-line no-await-in-loop
    importedModules[name] = await import(/* @vite-ignore */ path) as OpenSTAManager.ImportedModule;
    importedModules[name].bootstrap?.();
  }
}

await createInertiaApp({
  title: ((title) => `${title} - OpenSTAManager`),
  resolve: async (name: string) => {
    const split = name.split('::');

    if (split.length === 1) {
      // Load bundled page
      const imported = await import(`./Views/${name}.tsx`) as Record<string, Page | any>;
      return imported[name] as ClassComponent;
    }

    // Load page from module
    const [modulePath, page] = split;

    const osmModule = modulePath in importedModules
      ? importedModules[modulePath]
      : await import(/* @vite-ignore */ moduleURL.replace('{{modulePath}}', modulePath)) as OpenSTAManager.ImportedModule;

    return osmModule[page] as ClassComponent;
  },
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
    // eslint-disable-next-line no-void
    void showSnackbar(__('È ora possibile lavorare offline!'), false);
  }
}) as (reloadPage?: boolean) => Promise<void>;
