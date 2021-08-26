import classnames from 'classnames';
import Component from '../Component';

/**
 * Attributes:
 * - type: numeric, checkbox
 */
export default class TableHeadCell extends Component {
  view(vnode) {
    this.attrs.className = classnames('mdc-data-table__header-cell', vnode.attrs.className, {
      [`mdc-data-table__header-cell--${vnode.attrs.type}`]: vnode.attrs.type
    });
    return <th {...this.attrs} role="columnheader" scope="col">{vnode.children}</th>;
  }
}
