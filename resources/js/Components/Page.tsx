import {Collection} from 'collect.js';
import {
  Children,
  Vnode
} from 'mithril';

import {Component} from './Component';
import {TopAppBar} from './layout/TopAppBar';

export interface PageAttributes {
  page: {
    component: string
    locale: string
    props: Record<string, any> & {external?: boolean}
    url: string
    version: string
  };
}

// noinspection JSUnusedLocalSymbols
/**
 * The `Page` component
 *
 * @abstract
 */
export abstract class Page<A extends PageAttributes = PageAttributes> extends Component<A> {
  title?: string;

  view(vnode: Vnode<A>) {
    let contents = this.contents(vnode);
    if (contents instanceof Collection) {
      contents = contents.toArray();
    }

    return vnode.attrs.page.props.external ? contents : (
      <TopAppBar>
        {contents}
      </TopAppBar>
    );
  }

  contents(vnode: Vnode<A>): Children | Collection<Children> {
    return undefined;
  }
}
