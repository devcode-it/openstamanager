import m from 'mithril';
import {createInertiaApp} from '@maicol07/inertia-mithril';
import './_material';

// noinspection JSIgnoredPromiseFromCall
createInertiaApp({
  title: (title) => `${title} - OpenStaManager`,
  resolve: async (name) => import(`./Views/${name}`),
  setup({ el, app }) {
    m.mount(el, app);
  },
});
