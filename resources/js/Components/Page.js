import Component from './Component';

/**
 * The `Page` component
 *
 * @abstract
 */
export default class Page extends Component {
  page: {
    component: string,
    locale: string,
    props: Object,
    translations: Object,
    url: string,
    version: string,
    ...
  } = JSON.parse($('#app').attr('data-page'));

  __(key: string, replace: Object = {}) {
    let translation = this.page.translations[key]
      ? this.page.translations[key]
      : key;

    Object.keys(replace).forEach((k: string) => {
      translation = translation.replace(`:${k}`, replace[k]);
    });

    return translation;
  }
}
