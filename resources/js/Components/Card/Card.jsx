import {type ClassComponent} from 'mithril';

import Component from '../Component.jsx';
import Content from './Content.jsx';

export default class Card extends Component implements ClassComponent<{outlined?: boolean}> {
  view(vnode) {
    this.attrs.addClassNames('mdc-card', {
      'mdc-card--outlined': this.attrs.has('outlined')
    });

    return (
      <div {...this.attrs.all()}>
        <Content>{vnode.children}</Content>
      </div>
    );
  }
}
