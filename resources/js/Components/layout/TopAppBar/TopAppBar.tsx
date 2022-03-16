import {Inertia} from '@inertiajs/inertia';
import {Menu} from '@material/mwc-menu';
import {
  Component,
  Drawer,
  Footer,
  InertiaLink,
  Mdi,
  TopAppBarAction
} from '@osm/Components';
import {
  isMobile,
  Request
} from '@osm/utils';
import {IconButton} from '@osm/WebComponents';
import logo from '@osm-images/logo.png';
import {collect} from 'collect.js';
import {
  Vnode,
  VnodeDOM
} from 'mithril';
import prntr from 'prntr';

export interface TopAppBarAttributes {
}

export class TopAppBar extends Component<TopAppBarAttributes> {
  open = !isMobile();
  actionItems: Record<string, HTMLElement | IconButton | null> = {};
  menuItems: Record<string, HTMLElement | Menu | null> = {};

  view(vnode: Vnode<TopAppBarAttributes>) {
    return (
      <>
        <top-app-bar>
          <icon-button slot="navigationIcon">
            <Mdi icon="menu"/>
          </icon-button>

          <Drawer open={this.open}>
            {vnode.children}
          </Drawer>

          <div slot="title" style="display: flex; align-items: center;">
            <img src={logo} alt={__('OpenSTAManager')} style="height: 50px; margin-right: 8px;"/>
            <span>{__('OpenSTAManager')}</span>
          </div>

          {this.actions()
            .toArray()}
          <Footer/>
        </top-app-bar>
        {this.menus()
          .toArray()}
      </>
    );
  }

  oncreate(vnode: VnodeDOM<TopAppBarAttributes>) {
    super.oncreate(vnode);

    // TODO: Migliorare lo stile delle prossime righe
    // Load items
    this.actionItems = this.actions()
      .map((item) => document.querySelector<HTMLElement | IconButton>(item.attrs.id))
      .all() as unknown as Record<string, HTMLElement | IconButton | null>;
    this.menuItems = this.menus()
      .map((item) => document.querySelector<HTMLElement | Menu>(item.attrs.id))
      .all() as unknown as Record<string, HTMLElement | Menu | null>;

    // Handlers
    this.element.addEventListener('MDCTopAppBar:nav', () => {
      this.open = !this.open;
      m.redraw();
    });
  }

  actions() {
    return collect<Vnode<{id: string}>>({
      notifications: <TopAppBarAction id="navbar-notifications" aria-label={__('Notifiche')}
                                      icon="bell-outline"/>,
      print: (
        <icon-button id="navbar-print" slot="actionItems" aria-label={__('Stampa')}
                     onclick={this.printPage.bind(this)}>
          <Mdi icon="printer"/>
        </icon-button>
      ),
      periodSwitcher: (
        <icon-button id="navbar-period-switcher" slot="actionItems"
                     aria-label={__('Cambia periodo')}>
          <Mdi icon="calendar-range-outline"/>
        </icon-button>
      ),
      userInfo: (
        <icon-button id="navbar-user-info" slot="actionItems" aria-label={__('Il tuo profilo')}>
          {app.user && app.user.picture
            ? <img src={app.user.picture} alt={app.user.username} style="border-radius: 50%;"/>
            : <Mdi icon="account-outline"/>
          }
        </icon-button>
      )
    });
  }

  menus() {
    // TODO: Placeholder per il futuro sistema di notifiche (IDEA: Utilizzare una variabile
    // Globale per ottenere le notifiche dal server, o meglio, utilizzare una nuova API)

    // noinspection JSMismatchedCollectionQueryUpdate
    const notifications: string[] = [];
    // Utilizzare una variabile global
    return collect<Vnode<{id: string}>>({
      notifications: (
        <mwc-menu activatable corner="BOTTOM_RIGHT" id="notifications-list"
                  data-trigger="#navbar-notifications">
          <p>
            {__('{0} Non sono presenti notifiche|{1} C\'è una notifica|[2,*] Ci sono :num'
              + ' notifiche', {num: notifications.length})}
          </p>
          {notifications.map((notification) => (
            <mwc-list-item id="notification_{{$notification->id}}" key={crypto.randomUUID()}
                           graphic="icon" value="{{$notification->id}}">
              <Mdi icon="bell-outline" slot="graphic"></Mdi>
              <span>{notification}</span>
            </mwc-list-item>
          ))}
        </mwc-menu>
      ),
      userInfo: (
        <mwc-menu corner="BOTTOM_LEFT" id="user-info" data-trigger="#navbar-user-info">
          {app.user?.picture ? (
            <img class="user-image mdc-elevation--z2" src={app.user.picture}
                 alt={app.user.username}/>
          ) : <Mdi icon="account-outline"/>}
          <br/>
          <b style="margin-top: 16px;">{app.user?.username}</b>
          <br/>
          <span>{app.user?.email}</span>
          <br/>
          <InertiaLink href="">
            <mwc-button outlined label={__('Il tuo profilo')} className="mwc-button--rounded"
                        style="margin-top: 16px;">
              <Mdi icon="account-circle-outline" slot="icon"/>
            </mwc-button>
          </InertiaLink>
          <br/>
          <mwc-button id="logout-button" outlined label={__('Esci')} style="margin-top: 16px;"
                      onclick={this.logout.bind(this)}>
            <Mdi icon="logout-variant" slot="icon"/>
          </mwc-button>
        </mwc-menu>
      )
    });
  }

  printPage() {
    prntr({
      printable: 'appContent',
      type: 'html'
    });
  }

  async logout() {
    await Request.post(route('auth.logout'));
    Inertia.visit(route('auth.login'));
  }
}
