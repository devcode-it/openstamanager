import jQuery from 'jquery';
import m from 'mithril';
import {createInertiaApp} from '@maicol07/inertia-mithril';

global.$ = jQuery;
global.jQuery = jQuery;
global.m = m;

// noinspection JSIgnoredPromiseFromCall
createInertiaApp({
  title: (title) => `${title} - OpenStaManager`,
  resolve: async (name) => import(`./Views/${name}`),
  setup({ el, app }) {
    m.mount(el, app);
  },
});
