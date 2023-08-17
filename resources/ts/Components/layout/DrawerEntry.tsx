import '@material/web/icon/icon.js';
import '@material/web/list/list-item.js';

import {router} from '@maicol07/inertia-mithril';
import {ListItemEl} from '@material/web/list/internal/listitem/list-item';
import type * as MaterialIcons from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import {Vnode} from 'mithril';
import {
  Attributes,
  Component
} from 'mithril-utilities';
import {ValueOf} from 'type-fest';

type Icons = ValueOf<typeof MaterialIcons>;

export interface DrawerEntryAttributes extends Attributes, Partial<ListItemEl> {
  href: ListItemEl['href'];
  icon: Icons;
}

export class DrawerEntry<A extends DrawerEntryAttributes = DrawerEntryAttributes> extends Component<A> {
  view(vnode: Vnode<A>) {
    return (
      <md-list-item headline={vnode.children as string} active={this.isRouteActive(vnode.attrs.href)} href={vnode.attrs.href} onclick={this.navigateToRoute.bind(this)} {...this.attrs.all()}>
        <MdIcon icon={vnode.attrs.icon} slot="start"/>
      </md-list-item>
    );
  }

  isRouteActive(href: string) {
    return route(route().current()!, route().params) === href;
  }

  navigateToRoute(event: Event) {
    event.preventDefault();
    router.visit((event.target as ListItemEl).href);
  }
}
