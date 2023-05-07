import {router} from '@maicol07/inertia-mithril';
import '@material/web/button/outlined-button.js';
import '@material/web/button/text-button.js';
import {
  mdiAccountCircleOutline,
  mdiAccountOutline,
  mdiLogoutVariant
} from '@mdi/js';

import Dialog from '@osm/Components/Dialogs/Dialog';
import MdIcon from '@osm/Components/MdIcon';
import {Vnode} from 'mithril';
import {Request} from 'mithril-utilities';
import Stream from 'mithril/stream';

import TopAppBarAction from './TopAppBarAction';

export default class UserInfoAction extends TopAppBarAction {
  ariaLabel = __('Il tuo profilo');
  icon = mdiAccountOutline;
  id = 'navbar-notifications';
  dialogState = Stream(false);

  view(vnode: Vnode) {
    // TODO: Redo with flex columns and gap
    return [
      super.view(vnode),
      // eslint-disable-next-line mithril/jsx-key
      <Dialog open={this.dialogState}>
        <div style={{display: 'flex', flexDirection: 'column', gap: '8px'}}>
          {app.user?.picture ? (
            <img className="user-image mdc-elevation--z2" src={app.user.picture} alt={app.user.username}/>
          ) : <MdIcon className="user-image" icon={this.icon}/>}
          <b style="margin-top: 16px;">{app.user?.username}</b>
          <span>{app.user?.email}</span>
        </div>
        <md-outlined-button slot="footer">
          {this.ariaLabel}
          <MdIcon icon={mdiAccountCircleOutline} slot="icon"/>
        </md-outlined-button>
        <md-text-button id="logout-button" slot="footer" onclick={this.logout.bind(this)}>
          {__('Esci')}
          <MdIcon icon={mdiLogoutVariant} slot="icon"/>
        </md-text-button>
      </Dialog>
    ];
  }

  callback(): void {
    this.dialogState(true);
  }

  async logout() {
    await Request.post(route('logout'));
    router.visit(route('login'));
  }

  getIconElement(): JSX.Element {
    return app.user && app.user.picture ? <img src={app.user.picture} alt={app.user.username} style={{borderRadius: '50%'}}/> : super.getIconElement();
  }
}
