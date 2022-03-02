import {InertiaProgress} from '@inertiajs/progress';
import {createInertiaApp} from '@maicol07/inertia-mithril';
import cash from 'cash-dom';
import Mithril, {ClassComponent} from 'mithril';
// noinspection SpellCheckingInspection
import redaxios from 'redaxios';
import {registerSW} from 'virtual:pwa-register';

import {type Page} from './Components';
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

// @ts-ignore
const modules = globalThis.modules as OpenSTAManager.Modules;
for (const [name, module] of Object.entries(modules)) {
  if (module.hasBootstrap) {
    // eslint-disable-next-line no-await-in-loop
    importedModules[name] = await import(
      /* @vite-ignore */
      `${window.location.origin}/modules/${module.moduleVendor}/${name}/index.js`
      ) as OpenSTAManager.ImportedModule;

    importedModules[name].bootstrap?.();
  }
}

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

    const osmModule = modulePath in importedModules
      ? importedModules[modulePath]
      : await import(
        /* @vite-ignore */
        `${window.location.origin}/modules/${modulePath}/index.js`
        ) as OpenSTAManager.ImportedModule;

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

$('#logout-button')
  .on('click', async () => {
    await redaxios.post(route('auth.logout'), {}, {
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]')
          .attr('content')
      }
    });
    window.location.href = route('auth.login');
  });

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
