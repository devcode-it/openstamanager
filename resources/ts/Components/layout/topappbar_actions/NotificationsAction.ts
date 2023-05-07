import {mdiBellOutline} from '@mdi/js';
import TopAppBarAction from '@osm/Components/layout/topappbar_actions/TopAppBarAction';

export default class NotificationsAction extends TopAppBarAction {
  ariaLabel = __('Notifiche');
  icon = mdiBellOutline;
  id = 'navbar-notifications';

  callback(): void {
    //   <mwc-menu activatable corner="BOTTOM_RIGHT" id="notifications-list"
    //             Data-trigger="#navbar-notifications">
    //     <p>
    //       {__('{0} Non sono presenti notifiche|{1} C\'è una notifica|[2,*] Ci sono :num'
    //         + ' notifiche', {num: notifications.length})}
    //     </p>
    //     {notifications.map((notification) => (
    //       <mwc-list-item id="notification_{{$notification->id}}" key={crypto.randomUUID()}
    //                      Graphic="icon" value="{{$notification->id}}">
    //         <Mdi icon="bell-outline" slot="graphic"></Mdi>
    //         <span>{notification}</span>
    //       </mwc-list-item>
    //     ))}
    //   </mwc-menu>
  }
}
