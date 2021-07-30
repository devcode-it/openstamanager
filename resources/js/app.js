import m from 'mithril';
import {InertiaApp} from '@tebe/inertia-mithril';

const app = document.getElementById('app');

InertiaApp.initialPage = JSON.parse(app.dataset.page);
InertiaApp.resolveComponent = async (name) => (await import(`./Views/${name}`)).default;

m.mount(app, InertiaApp);
