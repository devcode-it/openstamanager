// noinspection JSUnusedGlobalSymbols

import '@material/mwc-snackbar';
import 'mithril';

import type {Cash} from 'cash-dom';
import type {Vnode} from 'mithril';
import {sync as render} from 'mithril-node-render';

type GenericObject = object & {prototype: any};

/**
 * Check if user is on a mobile device.
 *
 * @source https://stackoverflow.com/a/71030087/7520280
 */
export function isMobile() {
  return window.navigator.maxTouchPoints > 1;
}

/**
 * Check if class/object A is the same as or a subclass of class B.
 */
export function subclassOf(A: GenericObject, B: any): boolean {
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
 * Shows a snackbar
 *
 * @param {string} labelText
 * @param {number|false} timeoutMs Automatic dismiss timeout in milliseconds. Value must be
 * between 4000 and 10000 (or false to disable the timeout completely) or an error will be
 * thrown. Defaults to 5000 (5 seconds).
 * @param {string} actionText Text of the action button
 * @param {string} cancelText Text of the cancel button
 * @param {boolean} closeOtherSnackbars Whether to close other snackbars before showing this one.
 */
export async function showSnackbar(
  labelText: string,
  timeoutMs: number | false = 5000,
  actionText = 'OK',
  cancelText: string | false = false,
  closeOtherSnackbars = true
): Promise<string | 'action' | 'dismiss' | undefined> {
  return new Promise((resolve) => {
    if (closeOtherSnackbars) {
      const snackbars = document.querySelectorAll('mwc-snackbar');

      for (const snackbar1 of snackbars) {
        if (snackbar1.open) {
          snackbar1.close();
        }

        snackbar1.remove();
      }
    }

    const snackbar = document.createElement('mwc-snackbar');
    snackbar.labelText = labelText;
    snackbar.timeoutMs = timeoutMs || -1;

    if (actionText) {
      const button = document.createElement('mwc-button');
      button.label = actionText;
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

    snackbar.addEventListener('MDCSnackbar:closed', (event: Event & Partial<{detail: {reason?: string}}>) => {
      snackbar.close();
      resolve(event.detail?.reason);
    });

    snackbar.show();
  });
}
export function getFormData(form: Cash) {
  const data: Record<string, any> = {};
  const inputs = form.find('text-field, material-select, text-area');
  inputs.each((index, input) => {
    data[input.id] = (input as HTMLInputElement).value;
  });
  return data;
}
export function isFormValid(element: Cash | HTMLFormElement): boolean {
  let form = element;

  if (form instanceof HTMLFormElement) {
    form = $(form);
  }

  let isValid: boolean = true;
  form
    .find('text-field, text-area')
    .each((index: number, field: HTMLElement & Partial<HTMLInputElement>) => {
      if (!(field as HTMLInputElement).reportValidity()) {
        isValid = false;
      }
    });
  return isValid;
}

export function validatePassword(password: HTMLInputElement, passwordConfirm: HTMLInputElement) {
  if (password && passwordConfirm) {
    (passwordConfirm).setCustomValidity(
      (password).value !== (passwordConfirm).value
        // eslint-disable-next-line @typescript-eslint/no-use-before-define
        ? __('Le password non corrispondono')
        : ''
    );
  }
}

type ReplaceObject = Record<string, string | Vnode | number | boolean>;
/**
 * Ritorna una traduzione
 *
 * @param {string} key Stringa di cui prelevare la traduzione
 * @param {Object|boolean} replace Eventuali parametri da rimpiazzare.
 * Se il parametro è "true" (valore booleano), verrà ritornato il valore come stringa
 * (stesso funzionamento del parametro dedicato (sotto ↓))
 *
 * @returns {string} Stringa se non contiene HTML, altrimenti Vnode
 *
 * @protected
 */
// eslint-disable-next-line @typescript-eslint/naming-convention
export function __(key: string, replace: ReplaceObject = {}): string {
  let translation = key;

  if (app.translations && app.translations[key]) {
    translation = app.translations[key];
  }

  for (const [parameter, replacement] of Object.entries(replace)) {
    // `'attrs' in replacement` checks if `replacement` is a Mithril Vnode.
    const isVnode = typeof replacement === 'object' && 'attrs' in replacement;

    translation = translation.replace(
      `:${parameter}`,
      isVnode ? render(replacement) : replacement as string
    );
  }

  return translation;
}

export function getPropertyDescriptor(object: object, property: string) {
  return Object.getOwnPropertyDescriptor(Object.getPrototypeOf(object), property);
}

export function hasGetter(object: object, property: string): boolean {
  return getPropertyDescriptor(object, property)?.get !== undefined;
}

export function hasSetter(object: object, property: string): boolean {
  return getPropertyDescriptor(object, property)?.set !== undefined;
}
