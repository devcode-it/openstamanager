import '@material/web/list/list.js';

import {
  mdiAccountGroupOutline,
  mdiMenuOpen,
  mdiViewDashboardOutline
} from '@mdi/js';

import MdIcon from '@osm/Components/MdIcon';
import {VnodeCollectionItem} from '@osm/typings/jsx';
import {isMobile} from '@osm/utils/misc';
import {collect} from 'collect.js';
import {
  Children,
  Vnode
} from 'mithril';
import {
  Attributes,
  Component
} from 'mithril-utilities';
import Stream from 'mithril/stream';
import '../m3/NavigationDrawer';
import '../m3/NavigationDrawerModal';

import {DrawerEntry} from './DrawerEntry';

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
    // noinspection LocalVariableNamingConventionJS
    const DrawerTag = isMobile() ? 'md-navigation-drawer-modal' : 'md-navigation-drawer';
    return (
      <DrawerTag opened={this.open()}>
        {DrawerTag === 'md-navigation-drawer-modal' && <md-standard-icon-button onclick={this.onMobileMenuButtonClick.bind(this)}><MdIcon icon={mdiMenuOpen}/></md-standard-icon-button>}
        <md-list>{this.entries().values<VnodeCollectionItem>().all()}</md-list>
      </DrawerTag>
    );
  }

  entries() {
    return collect<VnodeCollectionItem>({
      dashboard: <DrawerEntry route="dashboard" icon={mdiViewDashboardOutline}>{__('Dashboard')}</DrawerEntry>,
      users: <DrawerEntry route="users.index" icon={mdiAccountGroupOutline}>{__('Utenti')}</DrawerEntry>
    });
  }

  onMobileMenuButtonClick() {
    this.open(!this.open());
  }
}
