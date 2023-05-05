// noinspection JSUnusedGlobalSymbols,OverlyComplexBooleanExpressionJS

import '@material/mwc-snackbar';
import '@material/web/button/text-button.js';

import {
  Vnode,
  VnodeDOM
} from 'mithril';

type GenericObject = object & {prototype: any};

export function mobileMediaQuery() {
  return window.matchMedia('only screen and (max-width: 480px)');
}

/**
 * Check if user is on a mobile device.
 *
 * @source https://stackoverflow.com/a/71030087/7520280
 */
export function isMobile() {
  return mobileMediaQuery().matches;
}

export function isVnode<A = any, S = undefined>(object_: any): object_ is Vnode<A, S> {
  // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
  return (object_ && !Array.isArray(object_) && object_.tag && object_.attrs) as boolean;
}

export function isVnodeDom<A = any, S = undefined>(object_: any): object_ is VnodeDOM<any, any> {
  // noinspection OverlyComplexBooleanExpressionJS
  return (isVnode<A, S>(object_) && 'dom' in object_ && object_.dom && object_.dom instanceof HTMLElement) as boolean;
}

export function capitalize(string_: string | any): string {
  return typeof string_ === 'string' ? string_.charAt(0).toUpperCase() + string_.slice(1) : '';
}

/**
 * Check if class/object A is the same as or a subclass of class B.
 */
export function subclassOf(object_: GenericObject, parentObject: any): boolean {
  return object_ && (object_ === parentObject || object_.prototype instanceof parentObject);
}

/**
 * Check if a string contains HTML code/tags
 */
export function containsHTML(string_: string): boolean {
  return /<(?<tag>[A-Za-z][\dA-Za-z]*)\b[^>]*>(?:.|\n)*?<\/\k<tag>>/.test(string_);
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
      const button = document.createElement('md-text-button');
      button.slot = 'action';
      button.textContent = actionText;
      snackbar.append(button);
    }

    if (cancelText) {
      const button = document.createElement('md-text-button');
      button.slot = 'cancel';
      button.textContent = cancelText;
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

export function isFormValid(form: HTMLFormElement): boolean {
  let isValid = true;
  for (const element of form.elements) {
    if (!(element as HTMLInputElement).reportValidity()) {
      isValid = false;
    }
  }

  return isValid;
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

export function createMap<K extends string | number | symbol = string, V = any>(entries: Record<K, V>) {
  return new Map(Object.entries(entries));
}
