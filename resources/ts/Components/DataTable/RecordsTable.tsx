import '@maicol07/material-web-additions/data-table/data-table-cell.js';
import '@maicol07/material-web-additions/data-table/data-table-column.js';
import '@maicol07/material-web-additions/data-table/data-table-footer.js';
import '@maicol07/material-web-additions/data-table/data-table-row.js';

import {
  DataTable as MdDataTable,
  RowSelectionChangedDetail
} from '@maicol07/material-web-additions/data-table/lib/data-table';
import {DataTableCell} from '@maicol07/material-web-additions/data-table/lib/data-table-cell';
import {mdiDeleteOutline} from '@mdi/js';
import DataTable, {DataTableAttributes} from '@osm/Components/DataTable/DataTable';
import DataTableColumn, {DataTableColumnAttributes} from '@osm/Components/DataTable/DataTableColumn';
import RecordsTableColumn from '@osm/Components/DataTable/RecordsTableColumn';
import MdIcon from '@osm/Components/MdIcon';
import Model from '@osm/Models/Model';
import {isVnode} from '@osm/utils/misc';
import collect, {Collection} from 'collect.js';
import {
  ToManyRelation,
  ToOneRelation
} from 'coloquent';
import {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import {Class} from 'type-fest';

export interface RecordsTableColumnAttributes extends DataTableColumnAttributes {
  label?: string;
}

export interface RecordsTableAttributes<M extends Model<any, any>> extends DataTableAttributes {
  cols: Collection<Children> | Collection<RecordsTableColumnAttributes> | Collection<Children | RecordsTableColumnAttributes>;
  records: Map<string, M>;
  readonly?: boolean;

  onTableRowClick?(recordId: string, event: MouseEvent): void;

  onDeleteRecordButtonClick?(recordId: string, event: MouseEvent): void;

  onDeleteSelectedRecordsButtonClick?(recordsIds: string[], event: MouseEvent): void;

  onRowSelectionChanged?(selectedRecordsIds: string[], event: CustomEventInit<RowSelectionChangedDetail>): void;

  valueModifier?(value: any, attribute: string, record: M): any;

  selectable?: boolean;
}

export default class RecordsTable<M extends Model<any, any>, A extends RecordsTableAttributes<M> = RecordsTableAttributes<M>> extends DataTable<A> {
  element!: MdDataTable;
  selectedRecordsIds: string[] = [];

  oninit(vnode: Vnode<A, this>) {
    super.oninit(vnode);

    vnode.attrs.paginated ??= true;
    vnode.attrs.currentPageSize ??= 10;
  }

  onupdate(vnode: VnodeDOM<A, this>) {
    super.onupdate(vnode);

    // Clear selected rows if new records are not included
    for (const id of this.selectedRecordsIds) {
      if (!vnode.attrs.records.has(id)) {
        this.selectedRecordsIds.splice(this.selectedRecordsIds.indexOf(id), 1);
      }
    }
  }

  contents(vnode: Vnode<A>) {
    return [
      this.tableColumns(vnode).values<Children>().all(),
      vnode.attrs.records.size === 0 ? this.noRecordsContent(vnode) : this.tableRows(vnode),
      this.tableFooter(vnode)
    ];
  }

  protected tableColumns(vnode: Vnode<A>) {
    let columns: Collection<Children> = collect({});
    if (vnode.attrs.selectable) {
      columns.put('checkbox', <md-data-table-column key="checkbox" type="checkbox"></md-data-table-column>);
    }

    // noinspection NestedFunctionJS
    function isDataTableColumn(column: Vnode<Record<string, any>>): boolean {
      return (typeof column.tag !== 'string' && (column.tag as Class<any>).prototype instanceof DataTableColumn);
    }

    columns = columns.merge(vnode.attrs.cols.map<Children>((column: Children | RecordsTableColumnAttributes, attribute: string) => {
      // If the column is a vnode, and it is a DataTableColumn or a string that matches the tag name of a DataTableColumn, then use it as is.
      if (isVnode<Record<string, any>>(column) && (column.tag === 'md-data-table-column' || isDataTableColumn(column))) {
        column.key ??= attribute;
        column.attrs['data-model-attribute'] ??= attribute;
        return column;
      }

      let attributes: DataTableColumnAttributes = {};
      let children: Children | RecordsTableColumnAttributes = column;

      if (RecordsTable.isRecordTableColumnAttributes(column)) {
        children = column.label ?? attribute;
        attributes = column;
      }

      attributes['data-model-attribute'] ??= attribute;

      // Otherwise, wrap it in a DataTableColumn
      return <RecordsTableColumn key={attribute} {...attributes}>{children}</RecordsTableColumn>;
    }).all());

    if (!vnode.attrs.readonly) {
      columns.put('actions', this.tableActionsColumn());
    }

    return columns;
  }

  protected tableActionsColumn(): Children {
    return (
      <md-data-table-column key="actions">{__('Azioni')}</md-data-table-column>
    );
  }

  noRecordsContent(vnode: Vnode<A>): Children {
    const colspan = vnode.attrs.cols.count() + (vnode.attrs.selectable ? 1 : 0) + (vnode.attrs.readonly ? 0 : 1);
    // noinspection JSXDomNesting
    return (
      <md-data-table-row>
        <td colspan={colspan} style={{textAlign: 'center'}}>{__('Nessun record trovato')}</td>
      </md-data-table-row>
    );
  }

  protected tableRows(vnode: Vnode<A>) {
    return [...this.tableRowsData(vnode)].map(([recordId, row]): Children => (
      <md-data-table-row key={recordId} data-model-id={recordId} onclick={this.onTableRowClick.bind(this, vnode, recordId)} style={{cursor: vnode.attrs.readonly ? undefined : 'pointer'}}>
        {vnode.attrs.selectable && <md-data-table-cell type="checkbox"></md-data-table-cell>}
        {row.map((cell, attribute: string) => <md-data-table-cell key={attribute}>{cell}</md-data-table-cell>).values<Children>().all()}
      </md-data-table-row>
    ));
  }

  protected tableRowsData(vnode: Vnode<A>) {
    const rows = new Map<string, Collection<string>>();

    for (const record of vnode.attrs.records.values()) {
      const cells = collect<string>({});

      for (const attribute of vnode.attrs.cols.keys()) {
        const value = this.getModelValue(record, attribute, vnode);
        cells.put(attribute, value);
      }

      if (!vnode.attrs.readonly) {
        cells.put('actions', this.tableRowActions(vnode, record).values<Children>().all());
      }

      rows.set(record.getId()!, cells);
    }

    return rows;
  }

  protected tableRowActions(vnode: Vnode<A>, record: M) {
    return collect<Children>({
      delete: (
        <md-standard-icon-button onclick={this.onDeleteRecordButtonClicked.bind(this, vnode, record)}>
          <MdIcon icon={mdiDeleteOutline}/>
        </md-standard-icon-button>
      )
    });
  }

  protected tableFooter(vnode: Vnode<A>) {
    return (
      <md-data-table-footer slot="footer" style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'right',
        gap: '4px'
      }}>
        {__('Seleziona un\'azione per la selezione multipla:')}
        {this.tableFooterActions(vnode).values<Children>().all()}
      </md-data-table-footer>
    );
  }

  protected tableFooterActions(vnode: Vnode<A>) {
    return collect<Children>({
      delete: (
        <md-text-button onclick={this.onDeleteSelectedRecordsButtonClicked.bind(this, vnode)} disabled={this.selectedRecordsIds.length === 0}>
          {__('Elimina selezionati')}
          <MdIcon icon={mdiDeleteOutline} slot="icon"/>
        </md-text-button>
      )
    });
  }

  oncreate(vnode: VnodeDOM<A, this>) {
    super.oncreate(vnode);

    this.element.addEventListener('rowSelectionChanged', this.onRowSelectionChanged.bind(this, vnode));
  }

  protected onRowSelectionChanged(vnode: Vnode<A>, event: CustomEventInit<RowSelectionChangedDetail>) {
    const recordId = event.detail!.row.dataset.modelId!;
    if (event.detail!.selected && !this.selectedRecordsIds.includes(recordId)) {
      this.selectedRecordsIds.push(recordId);
    } else if (!event.detail!.selected && this.selectedRecordsIds.includes(recordId)) {
      this.selectedRecordsIds.splice(this.selectedRecordsIds.indexOf(recordId), 1);
    }
    vnode.attrs.onRowSelectionChanged?.(this.selectedRecordsIds, event);
  }

  protected onTableRowClick(vnode: Vnode<A>, recordId: string, event: MouseEvent) {
    if (event.target instanceof DataTableCell && event.target.type === 'checkbox') {
      event.target.dispatchEvent(new CustomEvent('checked', {
        detail: {
          checked: !event.target?.checkbox?.checked
        }
      }));
      return;
    }
    vnode.attrs.onTableRowClick?.(recordId, event);
  }

  protected onDeleteRecordButtonClicked(vnode: Vnode<A>, record: M, event: MouseEvent) {
    event.stopPropagation();
    vnode.attrs.onDeleteRecordButtonClick?.(record.getId()!, event);
  }

  protected onDeleteSelectedRecordsButtonClicked(vnode: Vnode<A>, event: MouseEvent) {
    vnode.attrs.onDeleteSelectedRecordsButtonClick?.(this.selectedRecordsIds, event);
  }

  protected getModelValue(record: M, attribute: string, vnode: Vnode<A>): unknown {
    // Check if is a relation
    let value: unknown = record.getAttribute(attribute);
    if (attribute === 'id') {
      value = record.getId();
    }

    return vnode.attrs.valueModifier?.(value, attribute, record) ?? value;
  }

  private static isRecordTableColumnAttributes(column: Children | RecordsTableColumnAttributes): column is RecordsTableColumnAttributes {
    return typeof column === 'object' && 'label' in (column ?? {});
  }
}
