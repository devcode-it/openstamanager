import classnames from 'classnames';
import Component from '../Component';

export default class TableHeadRow extends Component {
  view(vnode) {
    this.attrs.className = classnames('mdc-data-table__header-row', vnode.attrs.className);
    return <tr {...this.attrs}>{vnode.children}</tr>;
  }
}
