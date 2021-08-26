import classnames from 'classnames';
import Component from '../Component';

export default class TableRow extends Component {
  view(vnode) {
    this.attrs.className = classnames('mdc-data-table__row', vnode.attrs.className);
    return <tr {...this.attrs}>{vnode.children}</tr>;
  }
}
