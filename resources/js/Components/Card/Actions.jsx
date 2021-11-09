import {type ClassComponent} from 'mithril';

import Component from '../Component.jsx';

export default class Actions extends Component implements ClassComponent<{'full-bleed'?: boolean}> {
  view(vnode) {
    this.attrs.addClassNames('mdc-card__actions', {
      'mdc-card__actions--full-bleed': this.attrs.has('full-bleed')
    });
    return (
      <div {...this.attrs.all()}>
        {vnode.children}
      </div>
    );
  }
}
