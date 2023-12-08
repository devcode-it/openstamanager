import '@material/web/list/list.js';
import '../../WebComponents/NavDrawer';

import {
  mdiAccountGroupOutline,
  mdiViewDashboardOutline
} from '@mdi/js';
import {VnodeCollectionItem} from '@osm/typings/jsx';
import {isMobile} from '@osm/utils/misc';
import {collect} from 'collect.js';
import {
  Children,
  Vnode
} from 'mithril';
import Stream from 'mithril/stream';
import {
  Attributes,
  Component
} from 'mithril-utilities';

import {DrawerItem} from './DrawerItem';

export interface DrawerAttributes extends Attributes {
  open: Stream<boolean>;
}

export default class Drawer<A extends DrawerAttributes = DrawerAttributes> extends Component<A> {
  open!: Stream<boolean>;

  oninit(vnode: Vnode<A, this>) {
    super.oninit(vnode);
    this.open = vnode.attrs.open ?? Stream(!isMobile());
  }

  view(vnode: Vnode<A>): Children {
    return (
      <nav-drawer {...vnode.attrs} open={this.open()} onclose={this.onDrawerClose.bind(this)}>
        {vnode.children}
        <md-list
          aria-label="List of pages"
          role="menubar"
          class="nav">
          {this.entries().values<VnodeCollectionItem>().all()}
        </md-list>
      </nav-drawer>
    );
  }

  entries() {
    return collect<VnodeCollectionItem>({
      dashboard: <DrawerItem href={route('dashboard')} icon={mdiViewDashboardOutline}>{__('Dashboard')}</DrawerItem>,
      users: <DrawerItem href={route('users.index')} icon={mdiAccountGroupOutline}>{__('Utenti')}</DrawerItem>
    });
  }

  onDrawerClose() {
    this.open(false);
  }
}
