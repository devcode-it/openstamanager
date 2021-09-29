import Component from '../Component.jsx';

export default class LayoutGrid extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-layout-grid', {
      'mdc-layout-grid--fixed-column-width': this.attrs.has('fixed'),
      [`mdc-layout-grid--align-${this.attrs.get('align')}`]: this.attrs.has('align')
    });

    return <div {...this.attrs.all()}>
      {vnode.children}
    </div>;
  }
}
