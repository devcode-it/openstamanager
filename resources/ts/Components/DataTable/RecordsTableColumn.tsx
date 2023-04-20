import {Vnode} from 'mithril';

import DataTableColumn, {DataTableColumnAttributes} from '~/Components/DataTable/DataTableColumn';

export interface RecordsTableColumnAttributes extends DataTableColumnAttributes {
}

export default class RecordsTableColumn<A extends RecordsTableColumnAttributes = RecordsTableColumnAttributes> extends DataTableColumn<A> {
  view(vnode: Vnode<A>): JSX.Element {
    vnode.attrs.customFiltering ??= true;
    vnode.attrs.customSorting ??= true;
    return super.view(vnode);
  }
}
