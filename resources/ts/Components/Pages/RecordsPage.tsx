/* eslint-disable sonarjs/no-duplicate-string */
import '@maicol07/material-web-additions/layout-grid/layout-grid.js';
import '@material/web/dialog/dialog.js';
import '@material/web/fab/branded-fab.js';
import '@material/web/fab/fab.js';
import '@material/web/iconbutton/icon-button.js';

import {router} from '@maicol07/inertia-mithril';
import {PaginateDetail} from '@maicol07/material-web-additions/data-table/internal/data-table';
import {
  FilterTextFieldInputEventDetail,
  SortButtonClickedEventDetail
} from '@maicol07/material-web-additions/data-table/internal/data-table-column.js';
import {
  mdiPlus,
  mdiRefresh
} from '@mdi/js';
import RecordsTable, {RecordsTableColumnAttributes} from '@osm/Components/DataTable/RecordsTable';
import AddEditRecordDialog from '@osm/Components/Dialogs/AddEditRecordDialog';
import DeleteRecordDialog, {DeleteRecordDialogAttributes} from '@osm/Components/Dialogs/DeleteRecordDialog';
import RecordDialog, {RecordDialogAttributes} from '@osm/Components/Dialogs/RecordDialog';
import MdIcon from '@osm/Components/MdIcon';
import Page, {PageAttributes} from '@osm/Components/Page';
import Record from '@osm/Models/Record';
import collect, {type Collection} from 'collect.js';
import dayjs from 'dayjs';
import type {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import Stream from 'mithril/stream';
import {Scope} from 'spraypaint';
import {SortDir} from 'spraypaint/lib-esm/scope';
import {match} from 'ts-pattern';
import {Match} from 'ts-pattern/dist/types/Match';
import type {Class} from 'type-fest';

type RecordDialogVnode<M extends Record, D extends RecordDialog<M>> = Vnode<RecordDialogAttributes<M>, D>;
type DeleteRecordDialogVnode<M extends Record, D extends DeleteRecordDialog<M>> = Vnode<DeleteRecordDialogAttributes<M>, D>;

// noinspection JSUnusedLocalSymbols
/**
 * @abstract
 */
export default abstract class RecordsPage<
  M extends Record,
  D extends AddEditRecordDialog<M> = AddEditRecordDialog<M>,
  DRD extends DeleteRecordDialog<M> = DeleteRecordDialog<M>
> extends Page {
  abstract modelType: Class<M> & typeof Record;
  recordDialogType?: Class<D>;
  deleteRecordDialogType?: Class<DRD>;

  protected addRecordDialogSlug: string = '__add_record_dialog__' as const;

  protected records = new Map<string, M>();
  protected isTableLoading = true;
  protected recordDialogsStates: Map<string | M, Stream<boolean>> = new Map();
  protected deleteRecordsDialogStates: Map<string, Stream<boolean>> = new Map();
  protected recordPageRouteName?: string;

  protected readonlyRecords = false;
  protected refreshRecords = true;

  protected currentPageSize = 50;
  protected pageSizes = [10, 25, 50, 100];
  protected currentPage = 1;
  protected lastPage = 1;
  protected firstRowOfPage = 1;
  protected totalRecords = 0;
  protected lastRowOfPage = this.totalRecords;

  protected filters: Map<string, string> = new Map();
  protected sort: Map<string, SortDir> = new Map();
  protected relatedFilters: Map<string, string> = new Map();
  private listenedFilterColumns: string[] = [];
  private listenedSortedColumns: string[] = [];

  oninit(vnode: Vnode<PageAttributes, this>) {
    super.oninit(vnode);
    // Redraw on a first load to call onbeforeupdate
    m.redraw();
  }

  async onbeforeupdate(vnode: VnodeDOM<PageAttributes, this>) {
    super.onbeforeupdate(vnode);

    if (this.refreshRecords) {
      this.refreshRecords = false;
      await this.loadRecords();
    }
  }

  async loadRecords() {
    this.isTableLoading = true;

    const query = this.modelQuery();

    const response = await query.page(this.currentPage).all();
    const rawResponse = response.raw;
    this.lastPage = rawResponse.meta?.last_page as number;
    this.firstRowOfPage = rawResponse.meta?.from as number;
    this.lastRowOfPage = rawResponse.meta?.to as number;
    this.currentPageSize = rawResponse.meta?.per_page as number;
    this.totalRecords = rawResponse.meta?.total as number;
    const {data} = response;

    this.records.clear();
    if (data.length > 0) {
      for (const record of data) {
        this.records.set(record.id, record);
      }
    }


    this.isTableLoading = false;
    m.redraw();
  }

  /**
   * Temporary
   *
   * @source https://stackoverflow.com/a/65003355/7520280
   */
  private static convertToSnakeCase(string_: string, trim = false, removeSpecials = false, underscoredNumbers = false) {
    return string_.replace(removeSpecials ? /[^\w ]/g : '', '')
      .replace(/[ _]+/g, '_')
      .replace(trim ? /(^_|_$)/gm : '', '')
      .replace(underscoredNumbers ? /([^\dA-Z_])([^_a-z])/g : /([^\dA-Z_])([^\d_a-z])/g, (m, preUpper: string, upper: string) => `${preUpper}_${upper}`)
      .replace(underscoredNumbers ? /([^\d_]\d|\d[^\d_])/g : '', (m, index: string) => (index ? [...index].join('_') : ''))
      .replace(/([A-Z])([A-Z])([^\dA-Z_])/g, (m, previousUpper: string, upper: string, lower: string) => `${previousUpper}_${upper}${lower}`)
      .replaceAll('_.', '.') // Remove redundant underscores
      .toLowerCase();
  }

  modelQuery() {
    let query: Scope<M> = this.modelType.per(this.currentPageSize);

    for (const [attribute, value] of this.filters) {
      query = query.where({[RecordsPage.convertToSnakeCase(attribute)]: value});
    }

    for (const [relation, value] of this.relatedFilters) {
      query = query.where({related: relation, search: value}); // TODO: Check
        // .where('search', value);
    }

    for (const [attribute, value] of this.sort) {
      query = query.order({[RecordsPage.convertToSnakeCase(attribute)]: value});
    }

    return query;
  }

  contents(vnode: Vnode<PageAttributes, this>) {
    return (
      <>
        <h2>{this.title}</h2>
        {this.table()}
        <div className="sticky-bottom" style={{display: 'flex', flexDirection: 'column', gap: '16px'}}>
          {this.fab().values().all()}
        </div>
        <>
          {...this.recordDialogs().values<Children>().all()}
          {...this.deleteRecordDialogs().values<Children>().all()}
        </>
      </>
    );
  }

  fab(): Collection<Vnode> {
    const fabs = collect<Vnode>({
      refresh: (
        <md-fab id="refresh-records" ariaLabel={__('Aggiorna')} onclick={this.onRefreshRecordsButtonClicked.bind(this)}>
          <MdIcon icon={mdiRefresh} slot="icon"/>
        </md-fab>
      )
    });

    if ((this.recordDialogType || this.recordPageRouteName) && !this.readonlyRecords) {
      fabs.put(
        'add',
        <md-fab id="add-record" ariaLabel={__('Aggiungi')} onclick={this.onAddNewRecordButtonClicked.bind(this)}>
          <MdIcon icon={mdiPlus} slot="icon"/>
        </md-fab>
      );
    }
    return fabs;
  }

  table(): Children {
    return (
      <RecordsTable<M>
        records={this.records}
        paginated
        selectable
        cols={this.tableColumns()}
        in-progress={this.isTableLoading}
        readonly={this.readonlyRecords}
        current-first-row={this.firstRowOfPage}
        current-last-row={this.lastRowOfPage}
        current-page-size={this.currentPageSize}
        page-sizes={JSON.stringify(this.pageSizes)}
        total-rows={this.totalRecords}
        onTableRowClick={this.onTableRowClick.bind(this)}
        onDeleteRecordButtonClick={this.onDeleteRecordButtonClicked.bind(this)}
        onDeleteSelectedRecordsButtonClick={this.onDeleteSelectedRecordsButtonClicked.bind(this)}
        onPageChange={this.onTablePageChange.bind(this)}
        valueModifier={(value: unknown, attribute: string, record: M) => this.cellValueModifier(value, attribute, record)
          .otherwise(() => value)}/>
    );
  }

  abstract tableColumns(): Collection<Children> | Collection<RecordsTableColumnAttributes> | Collection<Children | RecordsTableColumnAttributes>;

  protected onTableRowClick(recordId: string) {
    if (!this.readonlyRecords) {
      const model = this.records.get(recordId)!;
      this.updateRecord(model);
    }
  }

  updateRecord(model: M) {
    if (this.recordPageRouteName) {
      router.visit(route(this.recordPageRouteName, {id: model.id}));
      return;
    }

    const state = this.getRecordDialogState(model);
    state(true);
  }

  recordDialogs() {
    const collection = collect<RecordDialogVnode<M, D>>({});

    for (const [key, state] of this.recordDialogsStates) {
      // noinspection LocalVariableNamingConventionJS
      const RD = this.recordDialogType!;
      const record = key instanceof Record ? key : this.records.get(key);
      const vnodeKey = record?.id ?? (key as string);
      collection.put(vnodeKey, <RD key={vnodeKey} record={record} open={state}/>);
    }

    return collection;
  }

  deleteRecordDialogs() {
    const collection = collect<DeleteRecordDialogVnode<M, DRD>>({});

    for (const [key, state] of this.deleteRecordsDialogStates) {
      // noinspection LocalVariableNamingConventionJS
      const RD = this.deleteRecordDialogType ?? DeleteRecordDialog;
      const keyArray = key.split(',');
      const records = keyArray.map((recordId) => this.records.get(recordId)!);
      collection.put(key, <RD key={key} records={records} open={state}/>);
    }

    return collection;
  }

  onAddNewRecordButtonClicked() {
    if (this.recordDialogType) {
      this.openNewRecordDialog();
    } else if (this.recordPageRouteName) {
      router.visit(route(this.recordPageRouteName, {id: 'new'}));
    }
  }

  onRefreshRecordsButtonClicked() {
    this.refreshRecords = true;
  }

  onDeleteRecordButtonClicked(recordId: string, event: MouseEvent) {
    this.openDeleteRecordsDialog(this.records.get(recordId)!);
  }

  onDeleteSelectedRecordsButtonClicked(recordsIds: string[], event: MouseEvent) {
    this.openDeleteRecordsDialog(recordsIds.map((recordId) => this.records.get(recordId)!));
  }

  onTablePageChange(event: CustomEvent<PaginateDetail>) {
    const {pageSize, action} = event.detail;
    this.currentPageSize = pageSize;
    const {currentPage, lastPage} = this;
    match(action)
      .with('first', () => (this.currentPage = 1))
      .with('previous', () => (this.currentPage--))
      .with('next', () => (this.currentPage++))
      .with('last', () => (this.currentPage = lastPage))
      .with('current', () => {})
      .run();
    // We need to check if the page has changed
    // eslint-disable-next-line unicorn/consistent-destructuring
    if (currentPage !== this.currentPage) {
      this.refreshRecords = true;
      m.redraw();
    }
  }

  onupdate(vnode: VnodeDOM<PageAttributes, this>) {
    super.onupdate(vnode);

    const columns = this.element.querySelectorAll('md-data-table-column');
    for (const column of columns) {
      const attribute = column.dataset.modelAttribute!;
      if (!this.listenedFilterColumns.includes(attribute)) {
        column.addEventListener('filter', this.onFilter.bind(this));
        this.listenedFilterColumns.push(attribute);
      }

      if (!this.listenedSortedColumns.includes(attribute)) {
        column.addEventListener('sort', this.onColumnSort.bind(this));
        this.listenedSortedColumns.push(attribute);
      }
    }
  }

  protected onFilter(event: Event) {
    // TODO: Check if it's possible to use caseSensitiveness when filtering (currently enforces case insensitive)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const {text, column, caseSensitive} = (event as CustomEvent<FilterTextFieldInputEventDetail>).detail;
    const modelAttribute = (column.dataset.filterRelated === '' ? undefined : column.dataset.filterRelated) ?? column.dataset.filterAttribute ?? column.dataset.modelAttribute!;
    if (text === '') {
      this.filters.delete(modelAttribute);
      this.relatedFilters.delete(modelAttribute);
    } else if (column.dataset.filterRelated === undefined) {
      this.filters.set(modelAttribute, text);
    } else {
      this.relatedFilters.set(modelAttribute, text);
    }
    this.refreshRecords = true;
    m.redraw();
  }

  protected onColumnSort(event: Event) {
    const {column, isDescending} = (event as CustomEvent<SortButtonClickedEventDetail>).detail;
    const modelAttribute = column.dataset.sortAttribute ?? column.dataset.modelAttribute!;
    this.sort.clear();
    this.sort.set(modelAttribute, isDescending ? 'desc' : 'asc');
    this.refreshRecords = true;
    m.redraw();
  }

  openDeleteRecordsDialog(records: M | M[]) {
    const key = records instanceof Record ? records.id! : records.map((r) => r.id).join(',');
    let state = this.deleteRecordsDialogStates.get(key);

    if (!state) {
      state = Stream<boolean>();
      state.map((open) => {
        if (!open) {
          this.refreshRecords = true;
          m.redraw();
        }
        return open;
      });
      this.deleteRecordsDialogStates.set(key, state);
    }

    state(true);
  }

  openNewRecordDialog() {
    const state = this.getRecordDialogState(undefined, this.addRecordDialogSlug);
    state(true);
  }

  protected getRecordDialogState(record?: M, slug?: string) {
    const key: string = slug ?? record?.id ?? '';

    if (!this.recordDialogsStates.has(key)) {
      const state = Stream<boolean>(false);

      this.recordDialogsStates.set(key, state);

      state.map((open) => {
        if (!open) {
          this.isTableLoading = false;
          this.refreshRecords = true;
          m.redraw();
        }
        return open;
      });
    }

    return this.recordDialogsStates.get(key)!;
  }

  protected cellValueModifier(value: unknown, attribute: string, record: M): Match<string, unknown, string[], string> {
    return match(attribute)
      .returnType()
      .with('createdAt', 'updatedAt', () => dayjs(value as Date).format('DD/MM/YYYY HH:mm'));
  }
}
