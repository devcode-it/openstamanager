import {mdiPrinter} from '@mdi/js';
import prntr from 'prntr';

import TopAppBarAction from './TopAppBarAction';

export default class PrintAction extends TopAppBarAction {
  ariaLabel = __('Stampa');
  icon = mdiPrinter;
  id = 'navbar-print';

  callback(): void {
    // TODO: Not working with Web components: maybe this one? https://www.npmjs.com/package/dom-to-image-improved
    prntr({
      printable: 'appContent',
      type: 'html'
    });
  }
}
