import {type Cash} from 'cash-dom/dist/cash';
import {inRange} from 'lodash-es';

import Component from '../Component.jsx';

/**
 * Attributes:
 * - type: numeric, checkbox
 */
export default class TableCell extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-data-table__cell', {
      [`mdc-data-table__cell--${this.attrs.get('type')}`]: this.attrs.has('type')
    });

    if ((!vnode.children || vnode.children.length === 0) && this.attrs.get('type') === 'checkbox') {
      vnode.children = <mwc-checkbox className="mdc-data-table__row-checkbox"/>;
    }

    return <td {...this.attrs.all()}>{vnode.children}</td>;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    const checkboxes = (): Cash => $(this.element)
      .closest('.mdc-data-table')
      .find('tbody tr[checkable] mwc-checkbox');

    const cell: Cash = $(this.element);
    cell.children('mwc-checkbox').on('change', (event) => {
      const row = cell.parent();
      row.toggleClass('mdc-data-table__row--selected');

      const headerCheckbox = cell.closest('.mdc-data-table').find('thead th mwc-checkbox');
      const checks = checkboxes();
      const checked = checks.filter('[checked]');

      if (inRange(checked.length, 1, checks.length)) {
        headerCheckbox.prop('indeterminate', true);
        headerCheckbox.prop('checked', false);
      } else {
        headerCheckbox.prop('checked', checks.length === checked.length);
        headerCheckbox.prop('indeterminate', false);
      }
    });
  }
}
