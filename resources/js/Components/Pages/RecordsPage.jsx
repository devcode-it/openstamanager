import '@material/mwc-dialog';
import '@material/mwc-fab';

import collect from 'collect.js';
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

export type ColumnT = {
  id?: string,
  title: string,
  type: 'checkbox' | 'numeric' | null
}

/**
 * @abstract
 */
export default class RecordsPage extends Page {
  columns: Object<string | ColumnT> | Array<ColumnT>;

  rows: Array<Array<string>> = [];

  dialogs: Array<Children>;

  tableColumns(): Children {
    return collect(this.columns)
      .map(
        (column: ColumnT | string, id: string) => (
          <TableHeadCell id={id} key={id} {...(column ?? {})}>
            {typeof column === 'string' ? column : column.title}
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
