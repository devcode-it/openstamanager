import '@material/mwc-linear-progress';
import '@material/mwc-list/mwc-list-item';
import '@material/mwc-select';

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

          <tbody>
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
                {/* TODO: Rendere dinamico (permetti a chi chiama il componente di
                scegliere i valori da visualizzare */}
                <mwc-list-item value="10">10</mwc-list-item>
                <mwc-list-item value="25">25</mwc-list-item>
                <mwc-list-item value="50">50</mwc-list-item>
                <mwc-list-item value="75">75</mwc-list-item>
                <mwc-list-item value="100">100</mwc-list-item>
              </mwc-select>
            </div>

            <div className="mdc-data-table__pagination-navigation">
              <div className="mdc-data-table__pagination-total">
                {__('1-:chunk di :total', {chunk: <span id="chunk">10</span>, total: <span id="total">100</span>})}
              </div>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="first" disabled>
                <Mdi icon="page_first"/>
              </mwc-icon-button>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="prev" disabled>
                <Mdi icon="chevron_left"/>
              </mwc-icon-button>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="next">
                <Mdi icon="chevron_right"/>
              </mwc-icon-button>
              <mwc-icon-button className="mdc-data-table__pagination-button" data-page="last">
                <Mdi icon="chevron_last"/>
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
}
