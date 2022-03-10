import {Collection} from 'collect.js';
import {
  Children,
  Vnode
} from 'mithril';

import Component from './Component';
import TopAppBar from './layout/TopAppBar';

type PageAttributes = {
  component: string
  locale: string
  props: Record<string, any> & {external?: boolean}
  url: string
  version: string
};

/**
 * The `Page` component
 *
 * @abstract
 */
export default abstract class Page extends Component<{}> {
  page: PageAttributes = JSON.parse($('#app')
    .attr('data-page') as string) as PageAttributes;
  title?: string;

  view(vnode: Vnode) {
    let contents = this.contents();
    if (contents instanceof Collection) {
      contents = contents.toArray();
    }

    return this.page.props.external ? contents : (
      <TopAppBar>
        {contents}
      </TopAppBar>
    );
  }

  abstract contents(): Children | Collection<Children>;
}
