// noinspection JSUnusedGlobalSymbols

import type {Cash} from 'cash-dom/dist/cash';
import {type Vnode} from 'mithril';
import {sync as render} from 'mithril-node-render';

/**
 * Check if class/object A is the same as or a subclass of class B.
 */
export function subclassOf(A: { ... }, B: { ... }): boolean {
  // noinspection JSUnresolvedVariable
  return A && (A === B || A.prototype instanceof B);
}

/**
 * Check if a string contains HTML code/tags
 */
export function containsHTML(string_: string): boolean {
  // eslint-disable-next-line unicorn/better-regex
  return /<[a-z][\s\S]*>/i.test(string_);
}

/**
 * Show a snackbar
 */
export async function showSnackbar(message: string, duration: number = 5000, acceptText = 'OK', cancelText: ?string): Promise<boolean> {
  const snackbar = document.createElement('mwc-snackbar');
  snackbar.labelText = message;
  snackbar.timeoutMs = duration;
  if (acceptText) {
    const button = document.createElement('mwc-button');
    button.label = acceptText;
    button.slot = 'action';
    snackbar.append(button);
  }
  if (cancelText) {
    const button = document.createElement('mwc-button');
    button.label = cancelText;
    button.slot = 'cancel';
    snackbar.append(button);
  }
  document.body.append(snackbar);

  // eslint-disable-next-line unicorn/consistent-function-scoping
  const response = (value?: boolean) => value;

  // noinspection JSUnusedLocalSymbols
  const reasonPromise = new Promise((resolve, reject) => {});
  snackbar.addEventListener('MDCSnackbar:closed', (event) => {
    response(event?.detail?.reason === 'action' ?? false);
  });
  snackbar.show();
  snackbar.addEventListener('MDCSnackbar:closed', () => {
    snackbar.remove();
  });
  return reasonPromise;
}

export function isFormValid(element: Cash | HTMLFontElement) {
  let form = element;

  if (form instanceof HTMLFormElement) {
    form = $(form);
  }

  let isValid: boolean = true;

  form.find('text-field, text-area')
    .each((index: number, field: HTMLInputElement) => {
      if (!field.reportValidity()) {
        isValid = false;
      }
    });

  return isValid;
}

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
export function __(
  key: string | Vnode,
  replace: { [string]: string | Vnode | any } | boolean = {},
  returnAsString: boolean = false
): Vnode | string {
  let translation = key;
  // noinspection JSUnresolvedVariable
  if (window.translations && window.translations[key]) {
    translation = window.translations[key];
  }

  // Returns translation as string (no parameters replacement)
  if ((typeof replace === 'boolean' && replace) || (replace.length === 0 && !containsHTML(translation))) {
    return translation;
  }

  for (const k of Object.keys(replace)) {
    // `'attrs' in replace[k]` checks if `replace[k]` is a Mithril Vnode
    translation = translation.replace(`:${k}`, ((typeof replace[k] === 'object' && 'attrs' in replace[k]) ? render(replace[k]) : replace[k]));
  }

  if (returnAsString || !containsHTML(translation)) {
    return translation;
  }

  return window.m.trust(translation);
}
