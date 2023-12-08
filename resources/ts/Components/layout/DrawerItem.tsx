import '@material/web/icon/icon.js';
import '@material/web/list/list-item.js';

import {router} from '@maicol07/inertia-mithril';
import {ListItemEl} from '@material/web/list/internal/listitem/list-item';
import type * as MaterialIcons from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import {Vnode, VnodeDOM} from 'mithril';
import {
  Attributes,
  Component
} from 'mithril-utilities';
import {ValueOf} from 'type-fest';

type Icons = ValueOf<typeof MaterialIcons>;

export interface DrawerItemAttributes extends Attributes, Partial<ListItemEl> {
  href: ListItemEl['href'];
  icon: Icons;
}

export class DrawerItem<A extends DrawerItemAttributes = DrawerItemAttributes> extends Component<A> {
  oncreate(vnode: VnodeDOM<A, this>) {
    super.oncreate(vnode);
    this.element.setAttribute('href', vnode.attrs.href); // Fix for Mithril not setting the href attribute
  }

  view(vnode: Vnode<A>) {
    return (
      <md-list-item
        type="link"
        role="presentation"
        selected={this.isRouteActive(vnode.attrs.href)}
        href={vnode.attrs.href}
        onclick={this.navigateToRoute.bind(this, vnode.attrs.href)}
        {...this.attrs.all()}>
        <div slot="headline">
          {vnode.children}
        </div>
        <MdIcon icon={vnode.attrs.icon} slot="start"/>
      </md-list-item>
    );
  }

  isRouteActive(href: string) {
    return route(route().current()!, route().params) === href;
  }

  navigateToRoute(url: string, event: Event) {
    event.preventDefault();
    router.visit(url);
  }
}
