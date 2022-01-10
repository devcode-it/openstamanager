/* eslint-disable no-var,vars-on-top */
// noinspection ES6ConvertVarToLetConst

import type cash from 'cash-dom';
import type Mithril from 'mithril';
import type router from 'ziggy-js';

import type {__ as translator} from './utils';

declare global {
  let importPath: string;
  const translations: {[key: string]: string};
  const route: typeof router;

  var $: typeof cash;
  var m: typeof Mithril;
  // eslint-disable-next-line @typescript-eslint/naming-convention
  var __: typeof translator;
}
