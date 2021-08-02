import m from 'mithril';
import {createInertiaApp} from '@maicol07/inertia-mithril';

const app = document.getElementById('app');

// noinspection JSIgnoredPromiseFromCall
createInertiaApp({
  title: (title) => `${title} - OpenStaManager`,
  resolve: async (name) => import(`./Views/${name}`),
  setup({ el, app }) {
    m.mount(el, app);
  },
});
