import Component from '../Component.jsx';

/**
 * Attributes:
 * - type: numeric, checkbox
 */
export default class TableCell extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-data-table__cell', `mdc-data-table__cell--${this.attrs.get('type')}`);
    return <td {...this.attrs.all()}>{vnode.children}</td>;
  }
}
