/* eslint-disable @typescript-eslint/naming-convention */
// noinspection JSFileReferences,JSUnusedGlobalSymbols,LocalVariableNamingConventionJS
import 'inertia-plugin/client';
import 'vite-plugin-pwa/client.d';
import 'vite/client';

import type Mithril from 'mithril';
import type router from 'ziggy-js';

import type {
  __ as stringTranslator,
  _v as vnodeTranslator,
  tr as translator
} from '../utils/i18n';
import {OpenSTAManager} from './modules';

declare global {
  const route: typeof router;

  let app: {
    locale: string,
    theme: 'high-contrast' | 'light', // TODO: Da implementare
    user: OpenSTAManager.User | null,
    VERSION: string,
    REVISION: string,
  };

  // noinspection LocalVariableNamingConventionJS
  const LARAVEL_TRANSLATIONS: Record<string, Record<string, string>>;

  interface Window {
    m: typeof Mithril;
    tr: typeof translator;
    _v: typeof vnodeTranslator;
    __: typeof stringTranslator;
  }

  const m: typeof Mithril;
  const tr: typeof translator;
  // noinspection LocalVariableNamingConventionJS
  const _v: typeof vnodeTranslator;
  // noinspection LocalVariableNamingConventionJS
  const __: typeof stringTranslator;
}
