import {router} from '@maicol07/inertia-mithril';
import '@material/web/icon/icon.js';
import {ListItemLink} from '@material/web/list/lib/listitemlink/list-item-link';
import '@material/web/list/list-item-link.js';
import type * as MaterialIcons from '@mdi/js';
import {Vnode} from 'mithril';
import {ValueOf} from 'type-fest';

import {
  Attributes,
  Component
} from '~/Components/Component';
import MdIcon from '~/Components/MdIcon';

type Icons = ValueOf<typeof MaterialIcons>;

export interface DrawerEntryAttributes extends Attributes {
  route: string;
  icon: Icons;
}

export class DrawerEntry<A extends DrawerEntryAttributes = DrawerEntryAttributes> extends Component<A> {
  view(vnode: Vnode<A>) {
    return (
      <md-list-item-link headline={vnode.children as string} active={this.isRouteActive(vnode.attrs.route)} href={route(vnode.attrs.route)} onclick={this.navigateToRoute.bind(this)}>
        <MdIcon icon={vnode.attrs.icon} slot="start"/>
      </md-list-item-link>
    );
  }

  isRouteActive(routeName: string) {
    return route().current(routeName);
  }

  navigateToRoute(event: Event) {
    event.preventDefault();
    router.visit((event.target as ListItemLink).href);
  }
}
