import '@material/web/dialog/dialog.js';

import {Dialog as MDDialog} from '@material/web/dialog/lib/dialog';
import {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';

import {
  Attributes,
  Component
} from 'mithril-utilities';
import Stream from 'mithril/stream';

export interface DialogAttributes extends Attributes, Partial<Pick<MDDialog,
  'fullscreen' | 'fullscreenBreakpoint' | 'footerHidden' | 'stacked' | 'defaultAction' |
  'actionAttribute' | 'focusAttribute' | 'scrimClickAction' | 'escapeKeyAction' | 'modeless' |
  'draggable' | 'transition'
>> {
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
      <md-dialog {...vnode.attrs} open={this.open()}>
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
