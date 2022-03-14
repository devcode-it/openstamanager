import {collect} from 'collect.js';
import {
  Children,
  Vnode
} from 'mithril';

import {isMobile} from '../../../utils';
import Component from '../../Component';
import DrawerEntry from './DrawerEntry';

export interface DrawerAttributes {
  open: boolean;
}

export class Drawer extends Component<DrawerAttributes> {
  view(vnode: Vnode<DrawerAttributes>): Children {
    return (
      <material-drawer type={isMobile() ? 'modal' : 'dismissible'} open={vnode.attrs.open}>
        <mwc-list activatable>
          {this.entries()
            .toArray()}
        </mwc-list>
        <div id="appContent" slot="appContent">
          <main>
            {vnode.children}
          </main>
        </div>
      </material-drawer>
    );
  }

  entries() {
    const entries = collect({});
    entries.put('dashboard', <DrawerEntry route="dashboard"
                                          icon="view-dashboard-outline">{__('Dashboard')}</DrawerEntry>);
    return entries;
  }
}
