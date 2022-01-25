import {InertiaProgress} from '@inertiajs/progress';
import {createInertiaApp} from '@maicol07/inertia-mithril';
import cash from 'cash-dom';
import Mithril from 'mithril';
// noinspection SpellCheckingInspection
import redaxios from 'redaxios';
import {registerSW} from 'virtual:pwa-register';

import {type Page} from './Components';
import {OpenSTAManager} from './types/modules';
import {
  __ as translator,
  showSnackbar
} from './utils';

// Variabili globali
globalThis.$ = cash;
globalThis.m = Mithril;
globalThis.__ = translator;

InertiaProgress.init();

const importedModules: Record<string, {default: any, bootstrap?: Function}> = {};

// @ts-ignore
const modules = globalThis.modules as OpenSTAManager.Modules;
for (const [name, module] of Object.entries(modules)) {
  if (module.hasBootstrap) {
    // eslint-disable-next-line no-await-in-loop,@typescript-eslint/no-unsafe-assignment
    importedModules[name] = await import(
      /* @vite-ignore */
      `${importPath}/vendor/${name}/index.js`
    );

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

    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
    const osmModule: {default: unknown, [key: string]: unknown} = modulePath in importedModules
      ? importedModules[modulePath]
      : await import(
        /* @vite-ignore */
        `${importPath}/vendor/${modulePath}/index.js`
      );

    return osmModule[page];
  },
  setup({el, app}: {el: Element, app: Mithril.ComponentTypes}) {
    m.mount(el, app);
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
