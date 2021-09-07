import DataTable from '../DataTable/DataTable';
import TableBody from '../DataTable/TableBody';
import TableCell from '../DataTable/TableCell';
import TableHead from '../DataTable/TableHead';
import TableHeadCell from '../DataTable/TableHeadCell';
import TableHeadRow from '../DataTable/TableHeadRow';
import TableRow from '../DataTable/TableRow';
import Page from '../Page';

/**
 * @abstract
 */
export default class ListPage extends Page {
  columns: Array<{
    id: string,
    title: string,
    type: string | null
  }>;

  rows: Array<Array<string>> = [];

  view(vnode) {
    const columns = this.columns.map(
      (column, index) => (
        <TableHeadCell key={index}>
          {column}
        </TableHeadCell>
      )
    );

    const rows = this.rows.length > 0 ? this.rows.map((row, index) => (
      <TableRow key={index}>
        {row.map((cell, index_) => <TableCell key={index_}>{cell}</TableCell>)}
      </TableRow>
    )) : <TableRow><TableCell colspan={columns.length}>{this.__('Non sono presenti dati')}</TableCell></TableRow>;

    return (
      <>
        <h2>{this.title}</h2>
        <DataTable>
          <TableHead>
            <TableHeadRow>
              {columns}
            </TableHeadRow>
          </TableHead>
          <TableBody>
            {rows}
          </TableBody>
        </DataTable>
      </>
    );
  }
}
