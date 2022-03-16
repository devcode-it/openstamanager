import {
  Component,
  Mdi
} from '@osm/Components';
import {MaterialIcons} from '@osm/typings';
import {Vnode} from 'mithril';

export interface TopAppBarActionAttributes {
  id?: string,
  label?: string,
  icon?: MaterialIcons
}

export class TopAppBarAction extends Component<TopAppBarActionAttributes> {
  view(vnode: Vnode) {
    return (
      <icon-button id={this.attrs.pull('id') ?? crypto.randomUUID()} slot="actionItems"
                   aria-label={this.attrs.pull('label')} {...this.attrs.except(['icon'])
        .all()}>
        {this.attrs.has('icon')
          ? <Mdi icon={this.attrs.pull('icon') as MaterialIcons}/> : vnode.children}
      </icon-button>
    );
  }
}
