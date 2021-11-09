import {type ClassComponent} from 'mithril';

import Component from '../Component.jsx';

export default class Cell extends Component implements ClassComponent<{
  align?: string;
  columnspan?: number,
  'columnspan-desktop'?: number,
  'columnspan-tablet'?: number,
  'columnspan-phone'?: number,
  order?: number,
}> {
  view(vnode) {
    const spans = [];
    for (const device of ['desktop', 'tablet', 'phone']) {
      const key = `columnspan-${device}`;
      if (this.attrs.has(key)) {
        spans.push(`mdc-layout-grid__cell--span-${this.attrs.get(key)}-${device}`);
      }
    }

    this.attrs.addClassNames('mdc-layout-grid__cell', {
      [`mdc-layout-grid__cell--span-${this.attrs.get('columnspan')}`]: this.attrs.has('columnspan'),
      [`mdc-layout-grid__cell--order-${this.attrs.get('order')}`]: this.attrs.has('order'),
      [`mdc-layout-grid__cell--align-${this.attrs.get('align')}`]: this.attrs.has('align')
    }, spans);

    return <div {...this.attrs.all()}>
      {vnode.children}
    </div>;
  }
}
