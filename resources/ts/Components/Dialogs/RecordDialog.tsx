import '@material/web/button/text-button.js';
import '@maicol07/material-web-additions/layout-grid/layout-grid.js';

import {Vnode} from 'mithril';

import Dialog, {DialogAttributes} from '~/Components/Dialogs/Dialog';
import Model from '~/Models/Model';

export interface RecordDialogAttributes<M extends Model<any, any>> extends DialogAttributes {
  record?: M;
}

export default abstract class RecordDialog<M extends Model<any, any>, A extends RecordDialogAttributes<M> = RecordDialogAttributes<M>> extends Dialog<A> {
  protected record?: M;

  public oninit(vnode: Vnode<A, this>): void {
    super.oninit(vnode);
    this.record = vnode.attrs.record;
  }
}
