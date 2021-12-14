import '@material/mwc-checkbox';

import {
  type Children,
  type Vnode
} from 'mithril';
import PropTypes from 'prop-types';

import Component from '../Component.jsx';
import TableCell from './TableCell.jsx';

export default class TableRow extends Component {
  static propTypes = {
    checkable: PropTypes.bool
  };

  view(vnode) {
    this.attrs.addClassNames('mdc-data-table__row');

    return (
      <tr {...this.attrs.all()}>
        {this.checkbox(vnode.children)}
        {vnode.children}
      </tr>
    );
  }

  checkbox(children: Children[]): Children {
    if (!this.attrs.has('checkable')) {
      return <></>;
    }

    for (const child: Vnode of children) {
      if (child.attrs.type === 'checkbox') {
        break;
      }
    }

    return <TableCell type="checkbox"/>;
  }
}
