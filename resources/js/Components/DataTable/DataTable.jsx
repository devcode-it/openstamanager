import '@material/mwc-linear-progress';
import '@material/mwc-list/mwc-list-item';
import '@material/mwc-select';

import {type Cash} from 'cash-dom/dist/cash';
import {
  type Children,
  type Vnode
} from 'mithril';

import Component from '../Component.jsx';
import Mdi from '../Mdi.jsx';
import TableColumn from './TableColumn.jsx';
import TableFooter from './TableFooter.jsx';
import TableRow from './TableRow.jsx';

export default class DataTable extends Component {
  view(vnode) {
    return <div className="mdc-data-table" {...this.attrs.all()}>
      <div className="mdc-data-table__table-container">
        <table className="mdc-data-table__table" aria-label={this.attrs.get('aria-label')}>
          <thead>
            <tr className="mdc-data-table__header-row">
              {this.attrs.has('checkable')
                && <TableColumn type="checkbox">
                  <mwc-checkbox/>
                </TableColumn>}
              {this.tableColumns(vnode.children)}
            </tr>
          </thead>

          <tbody className="mdc-data-table__content">
            {this.tableRows(vnode.children)}
          </tbody>

          {this.tableFooter(vnode.children)}
        </table>

        {this.attrs.has('paginated') && <div className="mdc-data-table__pagination">
          <div className="mdc-data-table__pagination-trailing">
            <div className="mdc-data-table__pagination-rows-per-page">
              <div className="mdc-data-table__pagination-rows-per-page-label">
                {__('Righe per pagina')}
              </div>

              <mwc-select className="mdc-data-table__pagination-rows-per-page-select">
                {this.attrs.get('rows-per-page', '10,25,50,75,100').split(',').map(
                  (value) => {
                    const rowsPerPage = Number.parseInt(value, 10);
                    return (
                      <mwc-list-item key={rowsPerPage} value={rowsPerPage}>
                        {rowsPerPage}
                      </mwc-list-item>
                    );
                  }
                )}
              </mwc-select>
            </div>

            <div className="mdc-data-table__pagination-navigation">
              <div className="mdc-data-table__pagination-total">
                {__('1-:chunk di :total', {chunk: <span id="chunk">10</span>, total: <span id="total">100</span>})}
              </div>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="first" disabled>
                <Mdi icon="page-first"/>
              </mwc-icon-button>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="prev" disabled>
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

  tableColumns(children: Array<Children>) {
    return this.filterElements(children.flat(), TableColumn);
  }

  tableRows(children: Array<Children>) {
    let rows = this.filterElements(children.flat(), TableRow);

    if (this.attrs.has('checkable')) {
      rows = rows.map((row: Vnode) => (
          <TableRow key={row.attrs.key} checkable {...row.attrs}>
            {row.children}
          </TableRow>
      ));
    }

    return rows;
  }

  tableFooter(children: Array<Children>) {
    return this.filterElements(children.flat(), TableFooter);
  }

  filterElements(elements: Array<Children>, tag: Component | string): Array<Children> {
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

    $(this.element).find('thead th[sortable], thead th[sortable] mwc-icon-button-toggle').on('click', this.onColumnClicked.bind(this));
  }

  showProgress() {
    $(this.element).addClass('mdc-data-table--in-progress').find('.mdc-data-table__progress-indicator mwc-linear-progress').get(0).open();
  }

  hideProgress() {
    $(this.element).removeClass('mdc-data-table--in-progress').find('.mdc-data-table__progress-indicator mwc-linear-progress').get(0).open();
  }

  onColumnClicked(event: Event) {
    this.showProgress();

    const column: Cash = $(event.target).closest('th');
    const ascendingClass = 'mdc-data-table__header-cell--sorted';
    const descendingClass = 'mdc-data-table__header-cell--sorted-descending';

    // If it's already sorted change direction
    if (column.hasClass(ascendingClass)) {
      column.toggleClass(descendingClass);
    }

    // Clean previously sorted info and arrows
    const columns = $(this.element).find('thead th');
    columns.removeClass(ascendingClass);
    columns.find('mwc-icon-button-toggle').hide();

    // Add ony one header to sort
    column.addClass(ascendingClass);

    // Check if need descending sorting
    const isDescending = column.hasClass(descendingClass);

    // Do sorting
    this.sortTable(column.index() + 1, isDescending, column.attr('type') === 'numeric');
  }

  sortTable(columnIndex: number, isDescending: boolean, isNumeric: boolean) {
    const sorted = [...$(this.element).find(`tr td:nth-child(${columnIndex})`)].sort((a: HTMLElement, b: HTMLElement) => {
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

    for (const cell of sorted) {
      const row = $(cell).parent();
      row.appendTo(row.parent());
    }
  }
}
