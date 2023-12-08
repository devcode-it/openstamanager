import '@material/web/iconbutton/icon-button.js';
import '@osm/WebComponents/TopAppBar';

import {
  mdiMenu,
  mdiMenuOpen
} from '@mdi/js';
import logo from '@osm/../images/logo.png';
import NotificationsAction from '@osm/Components/layout/topappbar_actions/NotificationsAction';
import PeriodSwitcherAction from '@osm/Components/layout/topappbar_actions/PeriodSwitcherAction';
import PrintAction from '@osm/Components/layout/topappbar_actions/PrintAction';
import UserInfoAction from '@osm/Components/layout/topappbar_actions/UserInfoAction';
import MdIcon from '@osm/Components/MdIcon';
import {VnodeCollectionItem} from '@osm/typings/jsx';
import {
  isMobile,
  mobileMediaQuery
} from '@osm/utils/misc';
import {collect} from 'collect.js';
import {
  Vnode,
  VnodeDOM
} from 'mithril';
import Stream from 'mithril/stream';
import {
  Attributes,
  Component
} from 'mithril-utilities';

export interface TopAppBarAttributes extends Attributes {
  'drawer-open-state'?: Stream<boolean>;
}

export default class TopAppBar<A extends TopAppBarAttributes = TopAppBarAttributes> extends Component<A> {
  drawerOpenState!: Stream<boolean>;

  oninit(vnode: Vnode<A, this>) {
    super.oninit(vnode);
    this.drawerOpenState = vnode.attrs['drawer-open-state'] ?? Stream(!isMobile());
  }

  view(vnode: Vnode<A, this>) {
    return (
      <>
        <top-app-bar {...m.censor(vnode.attrs, ['drawer-open-state'])} drawer-open={this.drawerOpenState()} onmenu-button-toggle={this.onMenuButtonClick.bind(this)}>
          {this.navigationIcon(vnode)}

          <div slot="start">
            {this.start(vnode)}
          </div>

          <div slot="end">
            {this.actions().toArray()}
          </div>
        </top-app-bar>
      </>
    );
  }

  navigationIcon(vnode: Vnode<A, this>) {
    return (
      <>
        <MdIcon icon={mdiMenuOpen} slot="menu-button-icon-selected"/>
        <MdIcon icon={mdiMenu} slot="menu-button-icon"/>
      </>
    );
  }

  start(vnode: Vnode<A, this>) {
    return (
      <div style={{display: 'flex', alignItems: 'center'}}>
        {this.logo(vnode)}
        {this.title(vnode)}
      </div>
    );
  }

  logo(vnode: Vnode<A, this>) {
    return <img src={logo} alt={__('OpenSTAManager')} style={{height: '50px', marginRight: '8px'}}/>;
  }

  title(vnode: Vnode<A, this>) {
    return <span>{__('OpenSTAManager')}</span>;
  }

  oncreate(vnode: VnodeDOM<A, this>) {
    super.oncreate(vnode);

    mobileMediaQuery().addEventListener('change', (event) => {
      this.drawerOpenState(event.matches || this.drawerOpenState());
      m.redraw();
    });
  }

  end(vnode: Vnode<A, this>) {
    return (
      <>
        {this.actions().toArray()}
      </>
    );
  }

  actions() {
    return collect<VnodeCollectionItem>({
      notifications: <NotificationsAction/>,
      print: <PrintAction/>,
      periodSwitcher: <PeriodSwitcherAction/>,
      userInfo: <UserInfoAction/>
    });
  }

  onMenuButtonClick() {
    this.drawerOpenState(!this.drawerOpenState());
  }
}
