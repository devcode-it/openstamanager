import '@material/mwc-button';
import '@material/mwc-drawer';
import '@material/mwc-icon-button';
import '@material/mwc-list';
import '@material/mwc-menu';
import './WebComponents/TopAppBar';
import './WebComponents/MaterialDrawer';

import type {Dialog as MWCDialog} from '@material/mwc-dialog';
import type {Menu as MWCMenu} from '@material/mwc-menu';
import $ from 'cash-dom';

// Remove the ugly underline under mwc button text when inside <a> tags
$('a').has('mwc-button').css('text-decoration', 'none');

// Submit forms with enter key
$('mwc-button[type="submit"], mwc-icon-button[type="submit"]')
  .closest('form')
  .find('text-field, select, text-area')
  .on('keydown', function (event: KeyboardEvent) {
    if (event.key === 'Enter') {
      event.preventDefault();

      $(this).closest('form')
        .find('mwc-button[type=submit], mwc-icon-button[type="submit"]')
        .trigger('click');
    }
  });

const drawer = document.querySelector('material-drawer');
if (drawer) {
  drawer.parentElement.addEventListener('MDCTopAppBar:nav', () => {
    drawer.open = !drawer.open;
  });
}

$('mwc-menu').each((index, menu: MWCMenu) => {
  const trigger: Attr = menu.getAttribute('trigger');
  const button = trigger ? $(`#${trigger}`) : $(menu).prev();
  button.on('click', () => {
    menu.open = !menu.open;
  });
  menu.anchor = button.get(0);
});

$('mwc-dialog').each((index, dialog: MWCDialog) => {
  const trigger = dialog.getAttribute('trigger');
  const button = trigger ? $(`#${trigger}`) : $(dialog).prev('mwc-dialog-button');
  if (button) {
    button.on('click', () => {
      dialog.show();
    });
  }
});
