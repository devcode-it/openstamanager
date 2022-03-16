import {ListItem as MWCListItem} from '@material/mwc-list/mwc-list-item';
import {
  Component,
  InertiaLink,
  Mdi
} from '@osm/Components';
import {MaterialIcons} from '@osm/typings';
import {Vnode} from 'mithril';

export interface DrawerEntryAttributes {
  route: string;
  icon: MaterialIcons;
}

export class DrawerEntry extends Component<DrawerEntryAttributes> {
  view(vnode: Vnode<DrawerEntryAttributes>) {
    return (
      <InertiaLink className="drawer-item" href={route(vnode.attrs.route)}
                   onclick={this.onclick.bind(this)}>
        <mwc-list-item graphic="icon" activated={route()
          .current(vnode.attrs.route)}>
          <Mdi icon={vnode.attrs.icon} slot="graphic" aria-hidden="true"/>
          <span class="mdc-typography--subtitle2">
            {vnode.children}
          </span>
        </mwc-list-item>
      </InertiaLink>
    );
  }

  onclick() {
    (this.element.firstElementChild as MWCListItem).toggleAttribute('activated', true);
    $(this.element)
      .siblings('.drawer-item')
      .filter((index, item) => (item.firstElementChild as MWCListItem).activated)
      .children('mwc-list-item')
      .prop('activated', false);
  }
}
