import '@maicol07/material-web-additions/data-table/data-table-column.js';

import {DataTableColumn as MdDataTableColumn} from '@maicol07/material-web-additions/data-table/lib/data-table-column.js';
import {
  mdiArrowDown,
  mdiArrowUp
} from '@mdi/js';
import {Vnode} from 'mithril';

import {
  Attributes,
  Component
} from 'mithril-utilities';
import MdIcon from '~/Components/MdIcon';

export interface DataTableColumnAttributes extends Attributes, Partial<JSX.IntrinsicElements['md-data-table-column']> {
}

export default class DataTableColumn<A extends DataTableColumnAttributes = DataTableColumnAttributes> extends Component<A> {
  element!: MdDataTableColumn;

  view(vnode: Vnode<A>) {
    return (
      <md-data-table-column {...vnode.attrs}>
        <MdIcon icon={mdiArrowUp} slot="sort-icon-off"/>
        <MdIcon icon={mdiArrowDown} slot="sort-icon-on"/>
        {vnode.children}
      </md-data-table-column>
    );
  }
}
