import {Collection} from 'collect.js';
import {
  Children,
  Vnode
} from 'mithril';

import Component from './Component';
import TopAppBar from './layout/TopAppBar';

export interface PageAttributes {
  page: {
    component: string
    locale: string
    props: Record<string, any> & {external?: boolean}
    url: string
    version: string
  };
}

/**
 * The `Page` component
 *
 * @abstract
 */
export default abstract class Page extends Component<PageAttributes> {
  title?: string;

  view(vnode: Vnode<PageAttributes>) {
    let contents = this.contents();
    if (contents instanceof Collection) {
      contents = contents.toArray();
    }

    return vnode.attrs.page.props.external ? contents : (
      <TopAppBar>
        {contents}
      </TopAppBar>
    );
  }

  contents(): Children | Collection<Children> {
    return undefined;
  }
}
