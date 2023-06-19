import '@material/web/iconbutton/standard-icon-button.js';
import '@osm/WebComponents/TopAppBar';

import {
  mdiMenu,
  mdiMenuOpen
} from '@mdi/js';
import logo from '@osm/../images/logo.png';
import Drawer from '@osm/Components/layout/Drawer';
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

export default class TopAppBar extends Component {
  drawerOpenState = Stream(!isMobile());

  view(vnode: Vnode) {
    return (
      <>
        <top-app-bar>
          {this.navigationIcon(vnode)}

          <div style={{display: 'flex'}}>
            <Drawer open={this.drawerOpenState}/>
            <main id="appContent" style={{marginLeft: (!isMobile() && !this.drawerOpenState()) ? '16px' : undefined}}>
              {vnode.children}
            </main>
          </div>

          {this.branding(vnode)}

          {this.actions().toArray()}
        </top-app-bar>
      </>
    );
  }

  navigationIcon(vnode: Vnode) {
    return (
      <md-standard-icon-button slot="navigationIcon" onclick={() => this.drawerOpenState(!this.drawerOpenState())}>
        <MdIcon icon={this.drawerOpenState() ? mdiMenuOpen : mdiMenu}/>
      </md-standard-icon-button>
    );
  }

  branding(vnode: Vnode) {
    return (
      <div style={{display: 'flex', alignItems: 'center'}}>
        {this.logo(vnode)}
        {this.title(vnode)}
      </div>
    );
  }

  logo(vnode: Vnode) {
    return <img src={logo} alt={__('OpenSTAManager')} style={{height: '50px', marginRight: '8px'}}/>;
  }

  title(vnode: Vnode) {
    return <span>{__('OpenSTAManager')}</span>;
  }

  oncreate(vnode: VnodeDOM<Attributes, this>) {
    super.oncreate(vnode);

    this.element.addEventListener('MDCTopAppBar:nav', () => {
      this.drawerOpenState(!this.drawerOpenState());
    });

    mobileMediaQuery().addEventListener('change', (event) => {
      this.drawerOpenState(event.matches || this.drawerOpenState());
    });

    this.drawerOpenState.map((value) => {
      m.redraw();
      return value;
    });
  }

  actions() {
    return collect<VnodeCollectionItem>({
      notifications: <NotificationsAction/>,
      print: <PrintAction/>,
      periodSwitcher: <PeriodSwitcherAction/>,
      userInfo: <UserInfoAction/>
    });
  }
}
