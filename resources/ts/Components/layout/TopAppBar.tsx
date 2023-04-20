import '@material/web/iconbutton/standard-icon-button.js';
import '~/WebComponents/TopAppBar';

import {IconButton} from '@material/web/iconbutton/lib/icon-button';
import {Menu} from '@material/web/menu/lib/menu';
import {
  mdiMenu,
  mdiMenuOpen
} from '@mdi/js';
import {collect} from 'collect.js';
import {
  Vnode,
  VnodeDOM
} from 'mithril';
import Stream from 'mithril/stream';

import logo from '~/../images/logo.png';
import {
  Attributes,
  Component
} from '~/Components/Component';
import Drawer from '~/Components/layout/Drawer';
import NotificationsAction from '~/Components/layout/topappbar_actions/NotificationsAction';
import PeriodSwitcherAction from '~/Components/layout/topappbar_actions/PeriodSwitcherAction';
import PrintAction from '~/Components/layout/topappbar_actions/PrintAction';
import UserInfoAction from '~/Components/layout/topappbar_actions/UserInfoAction';
import MdIcon from '~/Components/MdIcon';
import {VnodeCollectionItem} from '~/typings/jsx';
import {
  isMobile,
  mobileMediaQuery
} from '~/utils/misc';

export default class TopAppBar extends Component {
  drawerOpenState = Stream(!isMobile());

  view(vnode: Vnode) {
    return (
      <>
        <top-app-bar>
          <md-standard-icon-button slot="navigationIcon">
            <MdIcon icon={this.drawerOpenState() ? mdiMenuOpen : mdiMenu}/>
          </md-standard-icon-button>

          <div style={{display: 'flex'}}>
            <Drawer open={this.drawerOpenState}/>
            <main id="appContent" style={{marginLeft: (!isMobile() && !this.drawerOpenState()) ? '16px' : undefined}}>
              {vnode.children}
            </main>
          </div>

          <div slot="title" style={{display: 'flex', alignItems: 'center'}}>
            <img src={logo} alt={__('OpenSTAManager')} style={{height: '50px', marginRight: '8px'}}/>
            <span>{__('OpenSTAManager')}</span>
          </div>

          {this.actions().toArray()}
        </top-app-bar>
      </>
    );
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
