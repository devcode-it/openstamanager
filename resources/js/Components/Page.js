import * as Mithril from 'mithril';
import * as render from 'mithril-node-render';
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
   * @param {string|Mithril.Vnode} key Stringa di cui prelevare la traduzione
   * @param {Object|boolean} replace Eventuali parametri da rimpiazzare.
   * Se il parametro è "true" (valore booleano), verrà ritornato il valore come stringa
   * (stesso funzionamento del parametro dedicato (sotto ↓))
   * @param {boolean} returnAsString Se impostato a "true" vien ritornata una stringa invece di
   * un Vnode di Mithril
   * @returns {Mithril.Vnode}
   *
   * @protected
   */
  __(
    key: string | Mithril.Vnode,
    replace: Object | boolean = {},
    returnAsString: boolean = false
  ): Mithril.Vnode {
    let translation = (this.page.translations && this.page.translations[key])
      ? this.page.translations[key] : key;

    // Ritorna la traduzione come stringa (senza sostituzione di parametri)
    if (replace === true) {
      return translation;
    }

    Object.keys(replace).forEach(async (k: string) => {
      // "'attrs' in replace[k]" controlla se replace[k] è un vnode di Mithril
      translation = translation.replace(`:${k}`, ((typeof replace[k] === 'object' && 'attrs' in replace[k]) ? render.sync(replace[k]) : replace[k]));
    });

    return returnAsString ? translation : Mithril.m.trust(translation);
  }
}
