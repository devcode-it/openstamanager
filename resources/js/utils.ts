// noinspection JSUnusedGlobalSymbols
import '@material/mwc-snackbar';
import 'mithril';

import type {Cash} from 'cash-dom/dist/cash';
import type {Vnode} from 'mithril';
import {sync as render} from 'mithril-node-render';

type GenericObject = object & {prototype: any};
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
  return new Promise((resolve, reject) => {
    if (closeOtherSnackbars) {
      const snackbars = document.querySelectorAll('mwc-snackbar');

      for (const snackbar of snackbars) {
        if (snackbar.open) {
          snackbar.close();
        }

        snackbar.remove();
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
  return Object.fromEntries<string | File>(new FormData(form[0] as HTMLFormElement));
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
 * @param {boolean} returnAsString Se impostato a "true" vien ritornata una stringa invece di
 * un Vnode di Mithril
 *
 * @returns {string} Stringa se non contiene HTML, altrimenti Vnode
 *
 * @protected
 */
// eslint-disable-next-line @typescript-eslint/naming-convention
export function __(
  key: string,
  replace: ReplaceObject | boolean = {},
  returnAsString: boolean = false
): string {
  let translation = key;

  // noinspection JSUnresolvedVariable
  if (translations && translations[key]) {
    translation = translations[key];
  }

  // Returns translation as string (no parameters replacement)
  if (replace === true
    || (typeof replace === 'object' && !containsHTML(translation))
  ) {
    return translation;
  }

  for (const k of Object.keys(replace)) {
    const replacement = (replace as ReplaceObject)[k];

    // `'attrs' in replacement` checks if `replacement` is a Mithril Vnode.
    translation = translation.replace(
      `:${k}`,
      typeof replacement === 'object' && 'attrs' in replacement
        ? render(replacement)
        : replacement as string
    );
  }

  if (returnAsString || !containsHTML(translation)) {
    return translation;
  }

  return translation;
}
