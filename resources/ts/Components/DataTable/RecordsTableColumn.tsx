import DataTableColumn, {DataTableColumnAttributes} from '@osm/Components/DataTable/DataTableColumn';
import {Vnode} from 'mithril';

export interface RecordsTableColumnAttributes extends DataTableColumnAttributes {
}

export default class RecordsTableColumn<A extends RecordsTableColumnAttributes = RecordsTableColumnAttributes> extends DataTableColumn<A> {
  view(vnode: Vnode<A>): JSX.Element {
    vnode.attrs.customFiltering ??= true;
    vnode.attrs.customSorting ??= true;
    return super.view(vnode);
  }
}
