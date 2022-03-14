import {Vnode} from 'mithril';

import {MaterialIcons} from '../../../typings';
import Component from '../../Component';
import Mdi from '../../Mdi';
import {TopAppBarAttributes} from '.';

export interface TopAppBarActionAttributes {
  id?: string,
  label?: string,
  icon?: MaterialIcons
}

export default class TopAppBarAction extends Component<TopAppBarAttributes> {
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
