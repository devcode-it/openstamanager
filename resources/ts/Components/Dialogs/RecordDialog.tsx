import '@maicol07/material-web-additions/layout-grid/layout-grid.js';
import '@material/web/button/text-button.js';

import Dialog, {DialogAttributes} from '@osm/Components/Dialogs/Dialog';
import Model from '@osm/Models/Model';
import {Vnode} from 'mithril';

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
