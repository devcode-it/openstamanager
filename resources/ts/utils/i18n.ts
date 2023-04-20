// noinspection FunctionNamingConventionJS

import {Vnode} from 'mithril';

export type ReplaceObject = Record<string, string | Vnode | number | boolean>;
export type I18n<B> = (B extends true ? string : Vnode<any, any>);

/**
 * @member {string} key String to translate.
 */
export interface TranslationParameters<B extends boolean | undefined> {
  replace?: ReplaceObject;
  forceString?: B;
}

/**
 * Get a Vnode containing the translated string.
 *
 * @param {string} key String to translate.
 * @param {?ReplaceObject} replace Object containing the parameters to replace in the string.
 * @param {boolean} forceString Whether to force the return value to be a string.
 *
 * @returns {string} String with replaced parameters if forceString is .
 *
 * @protected
 */
// eslint-disable-next-line @typescript-eslint/naming-convention
export function tr<B extends boolean | undefined>(key: string, {
  replace,
  forceString
}: TranslationParameters<B> = {replace: {}}): I18n<B> {
  let translation = key;
  const translations = app.translations[app.locale];

  if (translations && translations[key]) {
    translation = translations[key];
  }

  for (let [parameter, replacement] of Object.entries(replace ?? {})) {
    // `'attrs' in replacement` checks if `replacement` is a Mithril Vnode.
    const isVnode = typeof replacement === 'object' && 'attrs' in replacement;

    if (isVnode) {
      // Transform vnode to string.
      const div = document.createElement('div');
      m.render(div, replacement);
      replacement = div.innerHTML;
    }
    translation = translation.replace(`:${parameter}`, replacement as string);
  }

  return (forceString ? translation : m.trust(translation)) as I18n<B>;
}

// eslint-disable-next-line @typescript-eslint/naming-convention
export function __(key: string, replace?: ReplaceObject): string {
  return tr(key, {replace, forceString: true});
}

// eslint-disable-next-line @typescript-eslint/naming-convention
export function _v(key: string, replace?: ReplaceObject): Vnode<any, any> {
  return tr(key, {replace, forceString: false});
}

/**
 * Get the display name of a locale.
 *
 * @param locale The locale code to get the display name of. If not provided, the current locale will be used.
 */
export function getLocaleDisplayName(locale?: string) {
  if (!locale) {
    locale = app.locale;
  }

  const intl = new Intl.DisplayNames([app.locale], {type: 'language'});
  return intl.of(locale);
}

export function getFlag(language: string, slot: string = 'start') {
  // TODO: Wait https://github.com/material-components/material-web/issues/3933 to be fixed.
  return `<img class="flag" src="/vendor/blade-flags/language-${language}.svg" alt="${__('Bandiera della lingua :language', {language: getLocaleDisplayName(language) as string})}" slot="${slot}" style="margin-left: 16px;"/>`;
}
