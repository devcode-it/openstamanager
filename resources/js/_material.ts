import '@material/mwc-button';
import '@material/mwc-icon-button';
import '@material/mwc-list';
import '@material/mwc-menu';
import './WebComponents/TopAppBar';
import './WebComponents/MaterialDrawer';

import {Inertia} from '@inertiajs/inertia';
import type {MWCCard} from '@maicol07/mwc-card';
import type {LayoutGrid as MWCLayoutGrid, LayoutGridCell as MWCLayoutGridCell} from '@maicol07/mwc-layout-grid';
import type {Button as MWCButton} from '@material/mwc-button';
import type {Checkbox as MWCCheckbox} from '@material/mwc-checkbox';
import type {CircularProgress as MWCCircularProgress} from '@material/mwc-circular-progress';
import type {Dialog as MWCDialog} from '@material/mwc-dialog';
import type {Drawer as MWCDrawer} from '@material/mwc-drawer';
import type {Fab as MWCFab} from '@material/mwc-fab';
import type {Formfield as MWCFormfield} from '@material/mwc-formfield';
import type {IconButton as MWCIconButton} from '@material/mwc-icon-button';
import type {IconButtonToggle as MWCIconButtonToggle} from '@material/mwc-icon-button-toggle';
import type {LinearProgress as MWCLinearProgress} from '@material/mwc-linear-progress';
import type {List as MWCList} from '@material/mwc-list';
import type {ListItem as MWCListItem} from '@material/mwc-list/mwc-list-item';
import type {Menu as MWCMenu} from '@material/mwc-menu';
import $, {
  type Cash,
  type Element
} from 'cash-dom';

import type {JSXElement} from './typings';

// Declare Material JSX components
declare global {
  namespace JSX {
    interface IntrinsicElements {
      'mwc-button': JSXElement<MWCButton & {dialogAction?: string | 'ok' | 'discard' | 'close' | 'cancel' | 'accept' | 'decline'}>;
      'mwc-checkbox': JSXElement<MWCCheckbox>;
      'mwc-card': JSXElement<MWCCard>
      'mwc-circular-progress': JSXElement<MWCCircularProgress>;
      'mwc-dialog': JSXElement<MWCDialog>;
      'mwc-fab': JSXElement<MWCFab>;
      'mwc-formfield': JSXElement<MWCFormfield>;
      'mwc-icon-button': JSXElement<MWCIconButton>;
      'mwc-icon-button-toggle': JSXElement<MWCIconButtonToggle>;
      'mwc-layout-grid': JSXElement<MWCLayoutGrid>;
      'mwc-layout-grid-cell': JSXElement<MWCLayoutGridCell> & {'span-desktop'?: number, 'span-tablet'?: number, 'span-phone'?: number};
      'mwc-linear-progress': JSXElement<MWCLinearProgress>;
      'mwc-list': JSXElement<MWCList>;
      'mwc-list-item': JSXElement<MWCListItem>;
      'mwc-menu': JSXElement<MWCMenu>;
    }
  }
}

// Remove the ugly underline under mwc button text when inside <a> tags.
$('a').has('mwc-button').css('text-decoration', 'none');

// Submit forms with enter key
$('mwc-button[type="submit"], mwc-icon-button[type="submit"]')
  .closest('form')
  .find('text-field, select, text-area')
  .on('keydown', function (this: Cash, event: KeyboardEvent) {
    if (event.key === 'Enter') {
      event.preventDefault();

      $(this).closest('form')
        .find('mwc-button[type=submit], mwc-icon-button[type="submit"]')
        .trigger('click');
    }
  });

const drawer: MWCDrawer | null = document.querySelector('material-drawer');
if (drawer && drawer.parentElement) {
  drawer.parentElement.addEventListener('MDCTopAppBar:nav', () => {
    drawer.open = !drawer.open;
  });

  // Drawer items click
  $(drawer).find('a.drawer-item').on('click', function (this: HTMLAnchorElement, event: PointerEvent) {
    event.preventDefault();

    Inertia.visit(this.href);

    const drawerItem: Element & Partial<MWCListItem> | null = this.firstElementChild;
    if (drawerItem) {
      drawerItem.activated = true;
    }

    $(this).siblings('.drawer-item')
      .filter((index, item) => $(item).has('mwc-list-item[activated]').length > 0)
      .find('mwc-list-item')
      .prop('activated', false);
  });
}

$('mwc-menu').each((index, menu: HTMLElement & Partial<MWCMenu>) => {
  const trigger: string | null = menu.getAttribute('trigger');
  const button = trigger ? $(`#${trigger}`) : $(menu).prev();
  button.on('click', () => {
    menu.open = !menu.open;
  });
  menu.anchor = button.get(0);
});

$('mwc-dialog').each((index, dialog: HTMLElement & Partial<MWCDialog>) => {
  const trigger = dialog.getAttribute('trigger');
  const button = trigger ? $(`#${trigger}`) : $(dialog).prev('mwc-dialog-button');
  if (button) {
    button.on('click', () => {
      (dialog as MWCDialog).show();
    });
  }
});
