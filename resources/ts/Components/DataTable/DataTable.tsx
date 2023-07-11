import '@maicol07/material-web-additions/data-table/data-table.js';

import {DataTable as MdDataTable} from '@maicol07/material-web-additions/data-table/lib/data-table';
import {
  mdiChevronLeft,
  mdiChevronRight,
  mdiPageFirst,
  mdiPageLast
} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import {
  Children,
  Vnode
} from 'mithril';
import {
  Attributes,
  Component
} from 'mithril-utilities';
import {KebabCase} from 'type-fest';

type MdDataTableAttributes = {
  [K in keyof MdDataTable as KebabCase<K>]: MdDataTable[K];
};

export interface DataTableAttributes extends Attributes, Partial<MdDataTableAttributes> {
}

export default class DataTable<A extends DataTableAttributes = DataTableAttributes> extends Component<A> {
  view(vnode: Vnode<A>) {
    return (
      <md-data-table page-sizes-label={__('Righe per pagina:')} pagination-total-label={__(':firstRow-:lastRow di :totalRows')} {...vnode.attrs}>
        {this.contents(vnode)}
        <MdIcon icon={mdiPageFirst} slot="pagination-first-button-icon"/>
        <MdIcon icon={mdiChevronLeft} slot="pagination-previous-button-icon"/>
        <MdIcon icon={mdiChevronRight} slot="pagination-next-button-icon"/>
        <MdIcon icon={mdiPageLast} slot="pagination-last-button-icon"/>
      </md-data-table>
    );
  }

  contents(vnode: Vnode<A>): Children {
    return vnode.children;
  }
}
