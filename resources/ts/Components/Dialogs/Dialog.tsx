// noinspection IncorrectFormatting

import '@material/web/dialog/dialog.js';

import {Dialog as MDDialog} from '@material/web/dialog/lib/dialog';
import {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import Stream from 'mithril/stream';
import {
  Attributes,
  Component
} from 'mithril-utilities';
import {KebabCasedProperties} from 'type-fest';

export interface DialogAttributes extends Attributes, Partial<KebabCasedProperties<Omit<MDDialog, 'open' | 'style'>>> {
  open?: Stream<boolean>;
  onOpen?: () => void;
  onClose?: () => void;
}

export default abstract class Dialog<A extends DialogAttributes = DialogAttributes> extends Component<A> {
  declare element: MDDialog;
  open!: Stream<boolean>;

  oninit(vnode: Vnode<A, this>) {
    super.oninit(vnode);

    let {open} = vnode.attrs;
    if (open === undefined) {
      open = Stream(false);
    }

    this.open = open;
  }

  oncreate(vnode: VnodeDOM<A, this>) {
    super.oncreate(vnode);

    this.element.addEventListener('closed', () => {
      vnode.attrs.onClose?.();
      this.open(false);
      m.redraw();
    });

    this.element.addEventListener('opened', () => {
      vnode.attrs.onOpen?.();
    });
  }

  public view(vnode: Vnode<A, this>): Children {
    return (
      <md-dialog style={{
        '--md-dialog-container-max-block-size': 'calc(100% - 48px)',
        '--md-dialog-container-max-inline-size': 'calc(100% - 48px)'
      }} {...vnode.attrs} open={this.open()}>
        {this.contents(vnode) ?? vnode.children}
      </md-dialog>
    );
  }

  contents(vnode: Vnode<A, this>): Children {
    return undefined;
  }

  public show(): void {
    this.element.show();
  }

  public close(action?: string): void {
    this.element.close(action);
  }
}
