import '@material/mwc-checkbox';

import {collect} from 'collect.js';
import type {Children, Vnode} from 'mithril';

import Component from '../Component';
import TableCell from './TableCell';

export type TableRowAttributes = {checkable?: boolean};

export default class TableRow extends Component<TableRowAttributes> {
  view(vnode: Vnode<TableRowAttributes>) {
    this.attrs.addClassNames('mdc-data-table__row');
    return (
      <tr {...this.attrs.all()}>
        {this.checkbox(vnode.children as Children[])}
        {vnode.children}
      </tr>
    );
  }

  checkbox(children: Children[]): Children {
    if (!this.attrs.has('checkable')) {
      return <></>;
    }

    for (const child of children) {
      const attributes = collect((child as Vnode).attrs);

      if (attributes.get('type') === 'checkbox') {
        break;
      }
    }

    return <TableCell type="checkbox" />;
  }
}
