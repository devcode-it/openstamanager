import Component from '../Component.jsx';

export default class TableHeadRow extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-data-table__header-row');
    return <tr {...this.attrs.all()}>{vnode.children}</tr>;
  }
}
