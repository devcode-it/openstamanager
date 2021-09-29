import Component from '../Component.jsx';

export default class Row extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-layout-grid__inner');
    return <div {...this.attrs.all()}>{vnode.children}</div>;
  }
}
