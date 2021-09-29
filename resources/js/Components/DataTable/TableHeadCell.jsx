import Component from '../Component.jsx';

/**
 * Attributes:
 * - type: numeric, checkbox
 */
export default class TableHeadCell extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-data-table__header-cell', {
      [`mdc-data-table__header-cell--${this.attrs.get('type')}`]: this.attrs.has('type')
    });
    return <th {...this.attrs.all()} role="columnheader" scope="col">{vnode.children}</th>;
  }
}
