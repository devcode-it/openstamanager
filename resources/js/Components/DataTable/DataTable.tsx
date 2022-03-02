import '@material/mwc-linear-progress';
import '@material/mwc-list/mwc-list-item.js';
import '../../WebComponents/Select';

import type {LinearProgress as MWCLinearProgress} from '@material/mwc-linear-progress';
import type {Cash} from 'cash-dom';
import type {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';

import Component from '../Component';
import Mdi from '../Mdi';
import TableColumn from './TableColumn';
import TableFooter from './TableFooter';
import TableRow, {type TableRowAttributes} from './TableRow';

declare global {
  namespace JSX {
    interface IntrinsicElements {
      DataTable: DataTable
    }
  }
}

type PaginationAction = 'first' | 'next' | 'previous' | 'last';

export type Attributes = {
  'rows-per-page'?: number,
  'default-rows-per-page'?: number,
  'aria-label'?: string,
  checkable?: boolean,
  paginated?: boolean
};

export default class DataTable extends Component<Attributes> {
  rows: Children[] = [];
  columns: Children[];
  footer: Children[];
  rowsPerPage = {
    options: [10, 25, 50, 75, 100],
    currentStart: 0,
    value: 10,
    currentEnd: 10
  };

  oninit(vnode: Vnode<Attributes>) {
    super.oninit(vnode);

    let defaultRowsPerPage: number = Number.parseInt(this.attrs.get('default-rows-per-page', '10') as string, 10);

    if (Number.isInteger(defaultRowsPerPage)) {
      if (!this.rowsPerPage.options.includes(defaultRowsPerPage)) {
        [defaultRowsPerPage] = this.rowsPerPage.options;
      }

      this.rowsPerPage.value = defaultRowsPerPage;
    }
  }

  oncreate(vnode: VnodeDOM<Attributes>) {
    super.oncreate(vnode);

    $(this.element)
      .find('thead th.mdc-data-table__header-cell--with-sort')
      .on('click', this.onColumnClicked.bind(this));
    $(this.element)
      .find('.mdc-data-table__pagination-rows-per-page-select')
      .val(String(this.rowsPerPage.value))
      .on('selected', this.onPaginationSelected.bind(this));
    $(this.element)
      // eslint-disable-next-line sonarjs/no-duplicate-string
      .find('.mdc-data-table__pagination-button')
      .on('click', this.onPaginationButtonClicked.bind(this));
  }

  onbeforeupdate(vnode: VnodeDOM<Attributes, this>) {
    super.onbeforeupdate(vnode);
    const children = (vnode.children as Children[]).flat();
    this.rows = this.tableRows(children);
    this.columns = this.filterElements(children, TableColumn);
    this.footer = this.filterElements(children, TableFooter);
    const rowsPerPage = this.attrs.get('rows-per-page');

    if (rowsPerPage) {
      this.rowsPerPage.options = rowsPerPage
        .split(',')
        .map((value: string) => Number.parseInt(value, 10));


      if (this.rowsPerPage.currentStart === 0) {
        this.rowsPerPage.currentEnd = this.rowsPerPage.value >= this.rows.length
          ? this.rows.length
          : this.rowsPerPage.value;
      }
    }
  }

  onupdate(vnode: VnodeDOM<Attributes, this>) {
    super.onupdate(vnode);
    const rows: Cash = $(this.element).find('tbody tr');
    rows.hide().slice(this.rowsPerPage.currentStart, this.rowsPerPage.currentEnd).show();

    if (this.rowsPerPage.currentStart === 0) {
      this.paginate('first');
    }
  }

  view() {
    return (
      <div className="mdc-data-table" {...this.attrs.all()}>
        <div className="mdc-data-table__table-container">
          <table
            className="mdc-data-table__table"
            aria-label={this.attrs.get('aria-label')}
          >
            <thead>
              <tr className="mdc-data-table__header-row">
                {this.attrs.has('checkable') && <TableColumn type="checkbox" />}
                {this.columns}
              </tr>
            </thead>

            <tbody className="mdc-data-table__content">{this.rows}</tbody>

            {this.footer}
          </table>

          {this.attrs.has('paginated') && (
            <div className="mdc-data-table__pagination">
              <div className="mdc-data-table__pagination-trailing">
                <div className="mdc-data-table__pagination-rows-per-page">
                  <div className="mdc-data-table__pagination-rows-per-page-label">
                    {__('Righe per pagina')}
                  </div>

                  <material-select
                    outlined
                    className="mdc-data-table__pagination-rows-per-page-select"
                    fixedMenuPosition
                    // @ts-ignore
                    style="--mdc-select-width: 112px; --mdc-select-height: 36px; --mdc-menu-item-height: 36px;"
                  >
                    {this.rowsPerPage.options.map((rowsPerPage) => (
                      <mwc-list-item key={rowsPerPage} value={String(rowsPerPage)}>
                        {rowsPerPage}
                      </mwc-list-item>
                    ))}
                  </material-select>
                </div>

                <div className="mdc-data-table__pagination-navigation">
                  <div className="mdc-data-table__pagination-total">
                    {__(':start-:chunk di :total', {
                      start: this.rowsPerPage.currentStart + 1,
                      chunk: this.rowsPerPage.currentEnd > this.rows.length
                        ? this.rows.length
                        : this.rowsPerPage.currentEnd,
                      total: this.rows.length
                    })}
                  </div>
                  <mwc-icon-button
                    className="mdc-data-table__pagination-button"
                    data-page="first"
                    disabled
                  >
                    <Mdi icon="page-first" />
                  </mwc-icon-button>
                  <mwc-icon-button
                    className="mdc-data-table__pagination-button"
                    data-page="previous"
                    disabled
                  >
                    <Mdi icon="chevron-left" />
                  </mwc-icon-button>
                  <mwc-icon-button
                    className="mdc-data-table__pagination-button"
                    data-page="next"
                  >
                    <Mdi icon="chevron-right" />
                  </mwc-icon-button>
                  <mwc-icon-button
                    className="mdc-data-table__pagination-button"
                    data-page="last"
                  >
                    <Mdi icon="page-last" />
                  </mwc-icon-button>
                </div>
              </div>
            </div>
          )}

          <div className="mdc-data-table__progress-indicator">
            <div className="mdc-data-table__scrim" />
            <mwc-linear-progress
              className="mdc-data-table__linear-progress"
              indeterminate
            />
          </div>
        </div>
      </div>
    );
  }

  tableRows(children: Children[]): Children[] {
    let rows = this.filterElements(children, TableRow);

    if (this.attrs.has('checkable')) {
      rows = rows.map<Children>((row: Children) => {
        if (!row) {
          return '';
        }
        const rowNode = row as Vnode<TableRowAttributes>;
        return (
          <TableRow key={rowNode.key} checkable {...rowNode.attrs}>
            {rowNode.children}
          </TableRow>
        );
      });
    }

    return rows;
  }

  filterElements(
    elements: Children[],
    tag: typeof TableRow | typeof TableColumn | typeof TableFooter | string
  ): Children[] {
    const filtered = [];

    for (const element of elements) {
      if ((element as Vnode).tag === tag) {
        filtered.push(element);
      }
    }

    return filtered;
  }

  getProgress(): Element & Partial<MWCLinearProgress> | null {
    return this.element.querySelector('.mdc-data-table__progress-indicator mwc-linear-progress');
  }

  showProgress() {
    this.manageProgress(true);
  }

  hideProgress() {
    this.manageProgress(false);
  }

  onColumnClicked(event: Event) {
    this.showProgress();
    const column: Cash = $(event.target as Element).closest('th');
    const ascendingClass = 'mdc-data-table__header-cell--sorted';
    // Clean previously sorted info and arrows
    const columns = $(this.element).find('thead th');
    columns.removeClass(ascendingClass);
    columns.off('click').on('click', this.onColumnClicked.bind(this));
    // Add ony one header to sort
    column.addClass(ascendingClass);
    // Do sorting
    this.sortTable(column, false);
    // Set/remove callbacks
    column.off('click');
    column.find('mwc-icon-button-toggle').on('click', () => {
      this.sortTable(column);
    });
  }

  sortTable(column: Cash, toggleClass = true) {
    const cells = $(this.element)
      .find(`tr td:nth-child(${column.index() + 1})`)
      .get();

    // Handle button class
    if (toggleClass) {
      column.toggleClass('mdc-data-table__header-cell--sorted-descending');
    }

    const isNumeric = column.attr('type') === 'numeric';
    const isDescending = column.hasClass(
      'mdc-data-table__header-cell--sorted-descending'
    );
    cells.sort((a: HTMLElement, b: HTMLElement) => {
      let aValue: string | number = a.textContent as string;
      let bValue: string | number = b.textContent as string;

      if (isNumeric) {
        aValue = Number.parseFloat(aValue);
        bValue = Number.parseFloat(bValue);
      }

      if (!isDescending) {
        const temporary = aValue;
        aValue = bValue;
        bValue = temporary;
      }

      if (typeof aValue === 'string' && typeof bValue === 'string') {
        return aValue.localeCompare(bValue);
      }

      return aValue < bValue ? -1 : (aValue > bValue ? 1 : 0);
    });

    for (const cell of cells) {
      const row = $(cell).parent();
      row.appendTo(row.parent());
    }

    this.hideProgress();
  }

  onPaginationSelected(event: Event & {detail: {index: number}}) {
    const selectValue = $(event.target as HTMLFormElement).val();
    const rowsPerPage = Number.parseInt(selectValue as string, 10);
    this.rowsPerPage = {
      ...this.rowsPerPage,
      value: rowsPerPage,
      currentStart: 0,
      currentEnd: rowsPerPage
    };

    m.redraw();
  }

  onPaginationButtonClicked(event: Event) {
    const button: HTMLButtonElement | null = (event.target as HTMLElement).closest('.mdc-data-table__pagination-button');
    this.paginate(button?.dataset.page as PaginationAction);
    m.redraw();
  }

  paginate(action: PaginationAction) {
    if (action === 'first' || action === 'last') {
      const checkPagination = () => (action === 'first' ? this.rowsPerPage.currentStart > 0 : this.rowsPerPage.currentEnd < this.rows.length);

      let check = checkPagination();
      while (check) {
        this.paginate(action === 'first' ? 'previous' : 'next');
        check = checkPagination();
      }
    } else {
      const increments = {
        next: this.rowsPerPage.value,
        previous: -this.rowsPerPage.value
      };
      const increment = increments[action];

      this.rowsPerPage.currentStart += increment;
      if (this.rowsPerPage.currentStart < 0) {
        this.rowsPerPage.currentStart = 0;
      }

      this.rowsPerPage.currentEnd += increment;
    }

    const paginationButtons: NodeListOf<HTMLButtonElement> = this.element.querySelectorAll('.mdc-data-table__pagination-button');
    const disabled = {
      first: this.rowsPerPage.currentStart === 0,
      previous: this.rowsPerPage.currentStart === 0,
      next: this.rowsPerPage.currentEnd >= this.rows.length,
      last: this.rowsPerPage.currentEnd >= this.rows.length
    };

    for (const button of paginationButtons) {
      button.disabled = disabled[button.dataset.page as PaginationAction];
    }
  }

  private manageProgress(show: boolean) {
    $(this.element).toggleClass('mdc-data-table--in-progress');
    const progress = this.getProgress();
    if (progress) {
      (progress as MWCLinearProgress)[show ? 'open' : 'close']();
    }
  }
}
