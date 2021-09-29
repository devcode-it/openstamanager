import Component from '../Component.jsx';

export default class TableRow extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-data-table__row');
    return <tr {...this.attrs.all()}>{vnode.children}</tr>;
  }
}
