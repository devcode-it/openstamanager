import '@material/mwc-linear-progress';
import '@material/mwc-list/mwc-list-item';
import '../../WebComponents/Select';

import {type Cash} from 'cash-dom/dist/cash';
import {
  type Children,
  type Vnode
} from 'mithril';
import PropTypes from 'prop-types';

import Component from '../Component.jsx';
import Mdi from '../Mdi.jsx';
import TableColumn from './TableColumn.jsx';
import TableFooter from './TableFooter.jsx';
import TableRow from './TableRow.jsx';

export default class DataTable extends Component {
  static propTypes = {
    'rows-per-page': PropTypes.number,
    'default-rows-per-page': PropTypes.number,
    'aria-label': PropTypes.string,
    checkable: PropTypes.bool,
    paginated: PropTypes.bool
  };

  rows: Cash[] = [];
  columns: Children[];
  footer: Children[];

  rowsPerPage = {
    options: [10, 25, 50, 75, 100],
    currentStart: 0,
    value: 10,
    currentEnd: 10
  }

  onbeforeupdate(vnode) {
    super.onbeforeupdate(vnode);

    const children = (vnode.children: Children[]).flat();

    this.rows = this.tableRows(children);
    this.columns = this.filterElements(children, TableColumn);
    this.footer = this.filterElements(children, TableFooter);

    const rowsPerPage = this.attrs.get('rows-per-page');
    if (rowsPerPage) {
      this.rowsPerPage.options = rowsPerPage.split(',')
        .map((value: string) => Number.parseInt(value, 10));
    }

    let defaultRowsPerPage = this.attrs.get('default-rows-per-page', 10);
    if (typeof defaultRowsPerPage === 'string' && Number.isInteger(defaultRowsPerPage)) {
      defaultRowsPerPage = Number.parseInt(defaultRowsPerPage, 10);

      if (!this.rowsPerPage.options.includes(defaultRowsPerPage)) {
        [defaultRowsPerPage] = this.rowsPerPage.options;
      }
      this.rowsPerPage.value = defaultRowsPerPage;
    }

    if (this.rowsPerPage.currentStart === 0) {
      this.rowsPerPage.currentEnd = this.rowsPerPage.value >= this.rows.length ? this.rows.length
        : defaultRowsPerPage;
    }
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    const rows: Cash = $(this.element).find('tbody tr');
    rows.hide();

    // eslint-disable-next-line no-plusplus
    for (let index = this.rowsPerPage.currentStart; index < this.rowsPerPage.currentEnd; index++) {
      rows.eq(index).show();
    }

    if (this.rowsPerPage.currentStart === 0) {
      this.paginate('first');
    }

    $(this.element)
      .find('thead th.mdc-data-table__header-cell--with-sort')
      .on('click', this.onColumnClicked.bind(this));

    $(this.element).find('.mdc-data-table__pagination-rows-per-page-select')
      .on('selected', this.onPaginationSelected.bind(this));

    $(this.element).find('.mdc-data-table__pagination-button')
      .on('click', this.onPaginationButtonClicked.bind(this));
  }

  view(vnode) {
    return <div className="mdc-data-table" {...this.attrs.all()}>
      <div className="mdc-data-table__table-container">
        <table className="mdc-data-table__table" aria-label={this.attrs.get('aria-label')}>
          <thead>
          <tr className="mdc-data-table__header-row">
            {this.attrs.has('checkable') && <TableColumn type="checkbox"/>}
            {this.columns}
          </tr>
          </thead>

          <tbody className="mdc-data-table__content">
          {this.rows}
          </tbody>

          {this.footer}
        </table>

        {this.attrs.has('paginated') && <div className="mdc-data-table__pagination">
          <div className="mdc-data-table__pagination-trailing">
            <div className="mdc-data-table__pagination-rows-per-page">
              <div className="mdc-data-table__pagination-rows-per-page-label">
                {__('Righe per pagina')}
              </div>

              <material-select
                outlined
                className="mdc-data-table__pagination-rows-per-page-select"
                fixedMenuPosition
                style="--mdc-select-width: 112px; --mdc-select-height: 36px; --mdc-menu-item-height: 36px;"
              >
                {this.rowsPerPage.options.map(
                  rowsPerPage => (
                    <mwc-list-item
                      key={rowsPerPage}
                      value={rowsPerPage}
                      selected={this.rowsPerPage.value === rowsPerPage}
                    >
                      {rowsPerPage}
                    </mwc-list-item>
                  )
                )}
              </material-select>
            </div>

            <div className="mdc-data-table__pagination-navigation">
              <div className="mdc-data-table__pagination-total">
                {__(':start-:chunk di :total', {
                  start: this.rowsPerPage.currentStart + 1,
                  chunk: this.rowsPerPage.currentEnd,
                  total: this.rows.length
                })}
              </div>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="first"
                               disabled>
                <Mdi icon="page-first"/>
              </mwc-icon-button>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="previous"
                               disabled>
                <Mdi icon="chevron-left"/>
              </mwc-icon-button>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="next">
                <Mdi icon="chevron-right"/>
              </mwc-icon-button>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="last">
                <Mdi icon="page-last"/>
              </mwc-icon-button>
            </div>
          </div>
        </div>}

        <div className="mdc-data-table__progress-indicator">
          <div className="mdc-data-table__scrim"/>
          <mwc-linear-progress className="mdc-data-table__linear-progress" indeterminate/>
        </div>
      </div>
    </div>;
  }

  tableRows(children: Children[]): Children[] {
    let rows = this.filterElements(children, TableRow);

    if (this.attrs.has('checkable')) {
      rows = rows.map((row: Vnode) => (
        <TableRow key={row.attrs.key} checkable {...row.attrs}>
          {row.children}
        </TableRow>
      ));
    }

    return rows;
  }

  filterElements(elements: Children[], tag: Component | string): Children[] {
    const filtered = [];

    for (const element: Vnode of elements) {
      if (element.tag === tag) {
        filtered.push(element);
      }
    }

    return filtered;
  }

  oncreate(vnode) {
    super.oncreate(vnode);
  }

  showProgress() {
    $(this.element)
      .addClass('mdc-data-table--in-progress')
      .find('.mdc-data-table__progress-indicator mwc-linear-progress')
      .get(0)
      .open();
  }

  hideProgress() {
    $(this.element)
      .removeClass('mdc-data-table--in-progress')
      .find('.mdc-data-table__progress-indicator mwc-linear-progress')
      .get(0)
      .open();
  }

  onColumnClicked(event: Event) {
    this.showProgress();

    const column: Cash = $(event.target)
      .closest('th');
    const ascendingClass = 'mdc-data-table__header-cell--sorted';

    // Clean previously sorted info and arrows
    const columns = $(this.element)
      .find('thead th');
    columns.removeClass(ascendingClass);
    columns.off('click').on('click', this.onColumnClicked.bind(this));

    // Add ony one header to sort
    column.addClass(ascendingClass);

    // Do sorting
    this.sortTable(column.attr('id'), false);

    // Set/remove callbacks
    column.off('click');
    column.find('mwc-icon-button-toggle').on('click', () => {
      this.sortTable(column.attr('id'));
    });
  }

  sortTable(columnId: number, toggleClass = true) {
    const column: Cash = $(`#${columnId}`);
    const cells = $(this.element).find(`tr td:nth-child(${column.index() + 1})`).get();

    // Handle button class
    if (toggleClass) {
      column.toggleClass('mdc-data-table__header-cell--sorted-descending');
    }

    const isNumeric = column.attr('type') === 'numeric';
    const isDescending = column.hasClass('mdc-data-table__header-cell--sorted-descending');

    cells.sort((a: HTMLElement, b: HTMLElement) => {
      let aValue = a.textContent;
      let bValue = b.textContent;

      if (isNumeric) {
        aValue = Number.parseFloat(aValue);
        bValue = Number.parseFloat(bValue);
      }

      if (!isDescending) {
        const temporary = aValue;
        aValue = bValue;
        bValue = temporary;
      }

      if (typeof aValue === 'string') {
        return aValue.localeCompare(bValue);
      }

      return aValue < bValue ? -1 : (aValue > bValue ? 1 : 0);
    });

    for (const cell of cells) {
      const row = $(cell)
        .parent();
      row.appendTo(row.parent());
    }

    this.hideProgress();
  }

  onPaginationSelected(event: Event) {
    this.rowsPerPage.value = $(event.target).find('mwc-list-item').eq(event.detail.index).val();
    this.rowsPerPage.currentStart = 0;
    this.rowsPerPage.currentEnd = this.rowsPerPage.value;
    m.redraw();
  }

  onPaginationButtonClicked(event: Event) {
    const button: Cash = $(event.target);
    this.paginate(button.data('page'));
    m.redraw();
  }

  paginate(action: 'first' | 'next' | 'previous' | 'last') {
    const increments = {
      first: -this.rowsPerPage.currentStart,
      next: this.rowsPerPage.value,
      previous: -this.rowsPerPage.value,
      last: this.rows.length - this.rowsPerPage.currentStart
    };
    const increment = increments[action];

    if (action !== 'first') {
      this.rowsPerPage.currentStart += increment;
    }

    if (action !== 'last') {
      this.rowsPerPage.currentEnd += increment;
    }

    const paginationButtons: Cash = $(this.element).find('.mdc-data-table__pagination-button');
    const disabled = {
      first: this.rowsPerPage.currentStart === 0,
      previous: this.rowsPerPage.currentStart === 0,
      next: this.rowsPerPage.currentEnd >= this.rows.length,
      last: this.rowsPerPage.currentEnd >= this.rows.length
    };

    for (const button of paginationButtons) {
      const buttonElement = $(button);
      const buttonAction = buttonElement.data('page');
      buttonElement.prop('disabled', disabled[buttonAction]);
    }
  }
}
