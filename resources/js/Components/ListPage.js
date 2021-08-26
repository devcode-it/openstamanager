import Page from './Page';
import DataTable from './DataTable/DataTable';
import TableHeadCell from './DataTable/TableHeadCell';
import TableHead from './DataTable/TableHead';
import TableHeadRow from './DataTable/TableHeadRow';
import TableBody from './DataTable/TableBody';
import TableRow from './DataTable/TableRow';
import TableCell from './DataTable/TableCell';

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
      (column, i) => (
        <TableHeadCell key={i} type={null}>
          {column}
        </TableHeadCell>
      )
    );

    const rows = this.rows.length ? this.rows.map((row, i) => (
      <TableRow key={i}>
        {row.map((cell, index) => <TableCell key={index}>{cell}</TableCell>)}
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
