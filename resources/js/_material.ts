import '@material/mwc-button';
import '@material/mwc-list';
import '@material/mwc-menu';
import './WebComponents/IconButton';
import './WebComponents/MaterialDrawer';
import './WebComponents/TopAppBar';

import type {Dialog as MWCDialog} from '@material/mwc-dialog';
import $, {
  type Cash
} from 'cash-dom';

$(() => {
  // Submit forms with enter key
  $('mwc-button[type="submit"], mwc-icon-button[type="submit"]')
    .closest('form')
    .find('text-field, select, text-area')
    .on('keydown', function (this: Cash, event: KeyboardEvent) {
      if (event.key === 'Enter') {
        event.preventDefault();

        $(this)
          .closest('form')
          .find('mwc-button[type=submit], mwc-icon-button[type="submit"]')
          .trigger('click');
      }
    });


  $('mwc-dialog')
    .each((index, dialog: HTMLElement & Partial<MWCDialog>) => {
      const trigger = dialog.getAttribute('trigger');
      const button = trigger ? $(`#${trigger}`) : $(dialog)
        .prev('mwc-dialog-button');
      if (button) {
        button.on('click', () => {
          (dialog as MWCDialog).show();
        });
      }
    });
});
