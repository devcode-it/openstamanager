import '@material/web/button/text-button.js';

import {router} from '@maicol07/inertia-mithril';
import {mdiAccountOutline, mdiLogoutVariant} from '@mdi/js';
import Dialog, {DialogAttributes} from '@osm/Components/Dialogs/Dialog';
import MdIcon from '@osm/Components/MdIcon';
import {VnodeCollection} from '@osm/typings/jsx';
import collect from 'collect.js';
import Mithril from 'mithril';
import {Request} from 'mithril-utilities';

export default class UserInfoActionDialog extends Dialog {
  icon(): Mithril.Children {
    return app.user?.picture ? (
      <img className="user-image mdc-elevation--z2" src={app.user.picture} alt={app.user.username}/>
    ) : <MdIcon icon={mdiAccountOutline}/>;
  }

  headline(): Mithril.Children {
    return <span>{app.user?.username}</span>;
  }

  contents(vnode: Mithril.Vnode<DialogAttributes, this>): Mithril.Children {
    return (
      <span>{app.user?.email}</span>
    );
  }

  actions(vnode: Mithril.Vnode<DialogAttributes, this>): VnodeCollection {
    return collect({
      profile: (
        <md-text-button>{__('Il tuo profilo')}</md-text-button>
      ),
      logout: (
        <md-text-button id="logout-button" onclick={this.logout.bind(this)}>
          {__('Esci')}
          <MdIcon icon={mdiLogoutVariant} slot="icon"/>
        </md-text-button>
      )
    });
  }

  async logout() {
    await Request.post(route('logout'));
    router.visit(route('login'));
  }
}
