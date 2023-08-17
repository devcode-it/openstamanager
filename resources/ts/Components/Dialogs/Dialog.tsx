// noinspection IncorrectFormatting

import '@material/web/dialog/dialog.js';

import {Dialog as MDDialog} from '@material/web/dialog/internal/dialog';
import {
  VnodeCollection,
  VnodeCollectionItem
} from '@osm/typings/jsx';
import collect from 'collect.js';
import Mithril, {
  Children,
  Vnode
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

  public view(vnode: Vnode<A, this>): Children {
    let open = this.open();
    // If dialog is open but element isn't created yet, don't open it (wait for oncreate, see below)
    if (open && this.element === undefined) {
      open = false;
    }
    const contents = this.contents(vnode) ?? vnode.children;
    const actions = this.actions(vnode);
    return (
      <md-dialog style={{
        maxWidth: 'calc(100% - 48px)',
        maxHeight: 'calc(100% - 48px)'
      }} {...vnode.attrs} open={open} onopened={vnode.attrs.onOpen} onclosed={this.onDialogClosed.bind(this, vnode)}>
        {this.icon() && <div slot="icon">{this.icon()}</div>}
        {this.headline() && <div slot="headline">{this.headline()}</div>}
        {contents && <div slot="content">{contents}</div>}
        {actions.isNotEmpty() && <div slot="actions">{actions.toArray()}</div>}
      </md-dialog>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<A, this>) {
    super.oncreate(vnode);
    // TODO: [BUG] Dialog not opening by default on creation. Wait for https://github.com/material-components/material-web/issues/4728
    setTimeout(() => {
      if (this.open()) {
        void this.show();
      }
    }, 0);
  }

  onDialogClosed(vnode:Vnode<A, this>) {
    vnode.attrs.onClose?.();
    this.open(false);
  }

  icon(): Children {
    return undefined;
  }

  headline(): Children {
    return undefined;
  }

  contents(vnode: Vnode<A, this>): Children {
    return undefined;
  }

  actions(vnode: Vnode<A, this>): VnodeCollection {
    return collect<VnodeCollectionItem>();
  }

  public async show() {
    return this.element.show();
  }

  public async close(action?: string) {
    return this.element.close(action);
  }
}
