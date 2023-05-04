import '@maicol07/material-web-additions/data-table/data-table.js';

import {
  mdiChevronLeft,
  mdiChevronRight,
  mdiPageFirst,
  mdiPageLast
} from '@mdi/js';
import {
  Children,
  Vnode
} from 'mithril';

import {
  Attributes,
  Component
} from 'mithril-utilities';
import MdIcon from '~/Components/MdIcon';

export interface DataTableAttributes extends Attributes {
  inProgress?: boolean;
  paginated?: boolean;
  currentPageSize?: number;
  pageSizesLabel?: string;
  paginationTotalLabel?: string;
}

export default class DataTable<A extends DataTableAttributes = DataTableAttributes> extends Component<A> {
  view(vnode: Vnode<A>) {
    return (
      <md-data-table pageSizesLabel={__('Righe per pagina:')} paginationTotalLabel={__(':firstRow-:lastRow di :totalRows')} {...vnode.attrs}>
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
