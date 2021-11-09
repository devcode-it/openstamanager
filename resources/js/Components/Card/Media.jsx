import {type ClassComponent} from 'mithril';

import Component from '../Component.jsx';

export default class Media extends Component implements ClassComponent<{
  squadre?: boolean,
  'no-scaling'?: boolean,
  background?: string,
  title?: string
}> {
  view(vnode) {
    this.attrs.addClassNames('mdc-card__media', {
      'mdc-card__media--16-9': !this.attrs.has('no-scaling'),
      'mdc-card__media--square': this.attrs.has('square')
    });
    if (this.attrs.has('background')) {
      this.attrs.addStyles(`background-image: url("${this.attrs.get('background')}");`);
    }

    return (
      <div {...this.attrs.all()}>
        <div class="mdc-card__media-content">{this.attrs.get('title')}</div>
      </div>
    );
  }
}
