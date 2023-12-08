import autoAnimate from '@formkit/auto-animate';
import {ComponentAttributes} from '@maicol07/inertia-mithril';
import Footer from '@osm/Components/layout/Footer';
import {Collection} from 'collect.js';
import {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import {
  Attributes,
  Component
} from 'mithril-utilities';

import logoUrl from '../../images/logo_completo.png';
import Scaffold from '@osm/Components/layout/Scaffold';

export interface PageAttributes<A extends Record<string, any> & {external?: boolean} = Record<string, any>> extends Attributes, Required<ComponentAttributes<A>> {
}

// noinspection JSUnusedLocalSymbols
/**
 * The `Page` component
 *
 * @abstract
 */
export default abstract class Page<A extends PageAttributes = PageAttributes> extends Component<A> {
  title?: string;

  view(vnode: Vnode<A>) {
    let contents = this.contents(vnode);
    if (contents instanceof Collection) {
      contents = contents.flatten()
        .toArray();
    }

    return this.wrapContents(vnode, contents);
  }

  oncreate(vnode: VnodeDOM<A, this>) {
    super.oncreate(vnode);

    for (const element of this.element.querySelectorAll<HTMLElement>('[auto-animate]')) {
      autoAnimate(element);
    }

    if (this.title) {
      document.title = this.pageTitle();
    }
  }

  pageTitle(): string {
    return this.title ? `${this.title} - OpenSTAManager` : 'OpenSTAManager';
  }

  contents(vnode: Vnode<A>): Children | Collection<Children> {
    return undefined;
  }

  wrapContents(vnode: Vnode<A>, contents: Children): Children {
    return vnode.attrs.page.props.external ? (
      <div className="ext-container">
        <img src={logoUrl} className="center-logo" alt={__('OpenSTAManager')}/>
        <md-elevated-card className="ext-card">
          {contents}
        </md-elevated-card>
      </div>
    ) : (
      <Scaffold>
        {contents}
      </Scaffold>
    );
  }
}
