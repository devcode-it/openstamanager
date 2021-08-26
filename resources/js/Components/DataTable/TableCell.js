import classnames from 'classnames';
import Component from '../Component';

/**
 * Attributes:
 * - type: numeric, checkbox
 */
export default class TableCell extends Component {
  view(vnode) {
    this.attrs.className = classnames('mdc-data-table__cell', vnode.attrs.className, `mdc-data-table__cell--${vnode.attrs.type}`);
    return <td {...this.attrs}>{vnode.children}</td>;
  }
}
