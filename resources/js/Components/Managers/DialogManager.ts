import {Dialog} from '@material/mwc-dialog';

import {Manager} from './Manager';

export class DialogManager extends Manager {
  static selector = 'dialog';

  constructor(private dialog: Dialog) {
  }

  public show(dialog: Dialog) {
    this.dialog.show();
  }

  public hide() {
    this.dialog.close();
  }
}
