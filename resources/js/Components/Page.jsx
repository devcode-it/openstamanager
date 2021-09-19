import {Vnode} from 'mithril';
import {sync as render} from 'mithril-node-render';

import {containsHTML} from '../utils';
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

  /**
   * Ritorna una traduzione
   *
   * @param {string|Vnode} key Stringa di cui prelevare la traduzione
   * @param {Object|boolean} replace Eventuali parametri da rimpiazzare.
   * Se il parametro è "true" (valore booleano), verrà ritornato il valore come stringa
   * (stesso funzionamento del parametro dedicato (sotto ↓))
   * @param {boolean} returnAsString Se impostato a "true" vien ritornata una stringa invece di
   * un Vnode di Mithril
   *
   * @returns {Vnode}
   *
   * @protected
   */
  __(
    key: string | Vnode,
    replace: Object | boolean = {},
    returnAsString: boolean = false
  ): Vnode {
    let translation = (this.page.translations && this.page.translations[key])
      ? this.page.translations[key] : key;

    // Ritorna la traduzione come stringa (senza sostituzione di parametri)
    if ((typeof replace === 'boolean' && replace) || (replace.length === 0 && !containsHTML(translation))) {
      return translation;
    }

    for (const k of Object.keys(replace)) {
      // `'attrs' in replace[k]` controlla se replace[k] è un vnode di Mithril
      translation = translation.replace(`:${k}`, ((typeof replace[k] === 'object' && 'attrs' in replace[k]) ? render(replace[k]) : replace[k]));
    }

    if (returnAsString || !containsHTML(translation)) {
      return translation;
    }

    return window.m.trust(translation);
  }
}
