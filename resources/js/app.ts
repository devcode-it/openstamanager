import {InertiaProgress} from '@inertiajs/progress';
import {createInertiaApp} from '@maicol07/inertia-mithril';
import {Button} from '@material/mwc-button';
import {Menu as MWCMenu} from '@material/mwc-menu';
import cash from 'cash-dom';
import Mithril, {ClassComponent} from 'mithril';
import {registerSW} from 'virtual:pwa-register';

import type Page from './Components/Page';
import {OpenSTAManager} from './typings';
import {
  __ as translator,
  showSnackbar
} from './utils';
import {IconButton} from './WebComponents';

// Variabili globali
globalThis.$ = cash;
globalThis.m = Mithril;
globalThis.__ = translator;

InertiaProgress.init();

const importedModules: Record<string, OpenSTAManager.ImportedModule> = {};
const moduleURL = `${window.location.origin}/modules/{{modulePath}}/index.js`;

for (const [name, module] of Object.entries(modules)) {
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
      // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
      const {default: page}: {default: Page} = await import(`./Views/${name}.tsx`);
      return page;
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

    // TODO: Usare un evento custom
    // Inizializzazione componenti
    for (const menu of document.querySelectorAll<MWCMenu>('mwc-menu')) {
      const {trigger} = menu.dataset;
      if (trigger) {
        const button = document.querySelector<HTMLButtonElement | Button | IconButton>(trigger);
        if (button) {
          button.addEventListener('click', () => {
            menu.open = !menu.open;
          });
          menu.anchor = button;
        }
      }
    }

    // Remove the ugly underline under mwc button text when inside <a> tags.
    $('a')
      .has('mwc-button')
      .css('text-decoration', 'none');
  }
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
