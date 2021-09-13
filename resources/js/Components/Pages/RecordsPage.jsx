import {Children} from 'mithril';

import DataTable from '../DataTable/DataTable';
import TableBody from '../DataTable/TableBody';
import TableCell from '../DataTable/TableCell';
import TableHead from '../DataTable/TableHead';
import TableHeadCell from '../DataTable/TableHeadCell';
import TableHeadRow from '../DataTable/TableHeadRow';
import TableRow from '../DataTable/TableRow';
import Mdi from '../Mdi';
import Page from '../Page';

/**
 * @abstract
 */
export default class RecordsPage extends Page {
  columns: Array<{
    id: string,
    title: string,
    type: string | null
  }>;

  rows: Array<Array<string>> = [];

  dialogs: Array<Children>;

  tableColumns(): Children {
    return this.columns.map(
      (column, index) => (
        <TableHeadCell key={index}>
          {column}
        </TableHeadCell>
      )
    );
  }

  tableRows(): Children {
    if (this.rows.length === 0) {
      return (
        <TableRow>
          <TableCell colspan={this.columns.length}>
            {this.__('Non sono presenti dati')}
          </TableCell>
        </TableRow>);
    }

    return this.rows.map((row, index) => (
      <TableRow key={index}>
        {row.map((cell, index_) => <TableCell key={index_}>{cell}</TableCell>)}
      </TableRow>
    ));
  }

  view(vnode) {
    return (
      <>
        <h2>{this.title}</h2>
        <DataTable>
          <TableHead>
            <TableHeadRow>
              {this.tableColumns()}
            </TableHeadRow>
          </TableHead>
          <TableBody>
            {this.tableRows()}
          </TableBody>
        </DataTable>

        <mwc-fab id="add-record" label={this.__('Aggiungi')} class="sticky">
          <Mdi icon="plus" slot="icon"/>
        </mwc-fab>
        {this.dialogs}
      </>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    $('mwc-fab#add-record')
      .on('click', function () {
        $(this)
          .next('mwc-dialog#add-record-dialog')
          .get(0)
          .open();
      });
  }
}
