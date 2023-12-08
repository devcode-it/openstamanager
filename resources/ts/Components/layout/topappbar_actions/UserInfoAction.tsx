import {mdiAccountOutline} from '@mdi/js';
import UserInfoActionDialog from '@osm/Components/Dialogs/UserInfoActionDialog';
import {Vnode} from 'mithril';
import Stream from 'mithril/stream';

import TopAppBarAction from './TopAppBarAction';

export default class UserInfoAction extends TopAppBarAction {
  ariaLabel = __('Il tuo profilo');
  icon = mdiAccountOutline;
  id = 'navbar-userinfo';
  dialogState = Stream(false);

  view(vnode: Vnode) {
    return [
      super.view(vnode),
      // eslint-disable-next-line mithril/jsx-key
      <UserInfoActionDialog open={this.dialogState}/>
    ];
  }

  callback(): void {
    this.dialogState(true);
  }

  getIconElement(): JSX.Element {
    return app.user && app.user.picture ? <img src={app.user.picture} alt={app.user.username} style={{borderRadius: '50%'}}/> : super.getIconElement();
  }
}
