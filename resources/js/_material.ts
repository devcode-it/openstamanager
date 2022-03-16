import '@material/mwc-list';
import './WebComponents/IconButton';
import './WebComponents/MaterialDrawer';
import './WebComponents/TopAppBar';

import {Inertia} from '@inertiajs/inertia';
import {Button} from '@material/mwc-button';
import type {Dialog as MWCDialog} from '@material/mwc-dialog';
import {Menu as MWCMenu} from '@material/mwc-menu';
import {
  CircularProgressManager,
  DialogManager,
  LoadingButtonManager
} from '@osm/Components/Managers';
import $ from 'cash-dom';

import {IconButton} from './WebComponents';

/**
 * Handles the click event of the trigger button of a MWC Dialog or Menu.
 */
function triggerClickHandler(element: MWCMenu | MWCDialog) {
  if (element instanceof MWCMenu) {
    if (element.open) {
      element.close();
    } else {
      element.show();
    }
  } else {
    element.show();
  }
}

/**
 * Loads MWCMenu and MWCDialog triggers
 */
function loadTriggers() {
  for (const element of document.querySelectorAll<MWCMenu | MWCDialog>('mwc-menu, mwc-dialog')) {
    const {trigger} = element.dataset;
    if (trigger) {
      const button = document.querySelector<HTMLButtonElement | Button | IconButton>(trigger);
      if (button) {
        button.addEventListener('click', () => triggerClickHandler(element));

        if (element instanceof MWCMenu) {
          element.anchor = button;
        }
      }
    }
  }
}

function loadManagers() {
  const managers = [DialogManager, CircularProgressManager, LoadingButtonManager];

  for (const Manager of managers) {
    for (const element of document.querySelectorAll<HTMLElement>(Manager.selector)) {
      // eslint-disable-next-line unicorn/no-array-callback-reference
      if (Manager.filter(element)) {
        app.components[element.id] = new Manager(element);
      }
    }
  }
}

/**
 * Better forms accessibility
 * @example https://codesandbox.io/s/test-mwc-button-form-submit-zqgo5i?file=/src/index.ts
 */
function betterFormsAccessibility() {
  for (const button of document.querySelectorAll<Button>('mwc-button[type="submit"]')) {
    let previous = button.previousElementSibling;
    if (!previous || (previous && previous.getAttribute('type') !== 'submit')) {
      const input = document.createElement('input');
      input.setAttribute('type', 'submit');
      input.toggleAttribute('hidden', true);
      button.before(input);
      previous = button.previousElementSibling;
    }

    button.addEventListener('click', () => {
      button.closest('form')
        ?.requestSubmit(previous as HTMLInputElement);
    });
  }
}

Inertia.on('navigate', () => {
  loadTriggers();
  betterFormsAccessibility();
  loadManagers();

  // Remove the ugly underline under mwc button text when inside <a> tags.
  $('a')
    .has('mwc-button')
    .css('text-decoration', 'none');
});
