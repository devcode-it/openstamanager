import '@material/mwc-dialog';
import '@material/mwc-fab';
import '@material/mwc-snackbar';

import type {TextFieldInputMode, TextFieldType} from '@material/mwc-textfield/mwc-textfield-base';
import collect from 'collect.js';
import {snakeCase} from 'lodash/string';
import {Children} from 'mithril';

import {Model} from '../../Models';
import {showSnackbar} from '../../utils';
import DataTable from '../DataTable/DataTable.jsx';
import TableBody from '../DataTable/TableBody.jsx';
import TableCell from '../DataTable/TableCell.jsx';
import TableHead from '../DataTable/TableHead.jsx';
import TableHeadCell from '../DataTable/TableHeadCell.jsx';
import TableHeadRow from '../DataTable/TableHeadRow.jsx';
import TableRow from '../DataTable/TableRow.jsx';
import {Cell, LayoutGrid, Row} from '../Grid';
import Mdi from '../Mdi.jsx';
import Page from '../Page.jsx';


export type ColumnT = {
  id?: string,
  title: string,
  type?: 'checkbox' | 'numeric'
}

export type FieldT = {
  id?: string,
  value?: string,
  type?: TextFieldType,
  label?: string,
  placeholder?: string,
  prefix?: string,
  suffix?: string,
  icon?: string,
  iconTrailing?: string,
  disabled?: boolean,
  charCounter?: boolean,
  outlined?: boolean,
  helper?: string,
  helperPersistent?: boolean | string,
  required?: boolean,
  minLength?: number,
  maxLength?: number,
  validationMessage?: string,
  pattern?: string,
  min?: number | string,
  max?: number | string,
  size?: number | null,
  step?: number | null,
  autoValidate?: boolean,
  validity?: ValidityState,
  willValidate?: boolean,
  validityTransform?: (value: string, nativeValidity: ValidityState) => Partial<ValidityState> |
    null,
  validateOnInitialRender?: boolean,
  name?: string,
  inputMode?: TextFieldInputMode,
  readOnly?: boolean,
  autocapitalize: 'on' | 'off' | 'sentences' | 'none' | 'words' | 'characters',
  endAligned?: boolean,
  ...
};

export type SectionT = FieldT[] | {
  id?: string,
  heading?: string,
  columns?: number,
  fields: FieldT[] | { [string]: FieldT }
};

/**
 * @abstract
 */
export default class RecordsPage extends Page {
  columns: { [string]: [string] | ColumnT } | ColumnT[];
  rows: string[][] | Model[] = [];

  sections: { [string]: SectionT } | SectionT[];

  dialogs: Children[];

  model: Model;

  saveModelWithSnakeCase: boolean = true;

  async oninit(vnode) {
    // noinspection JSUnresolvedFunction
    vnode.state.data = (await this.model.all()).getData();
    if (vnode.state.data) {
      this.rows = vnode.state.data;
      m.redraw();
    }
  }

  tableColumns(): Children {
    return collect(this.columns)
      .map(
        (column: ColumnT | string, id: string) => (
          <TableHeadCell id={id} key={id} {...((typeof column === 'object') ? column : {})}>
            {typeof column === 'string' ? column : column.title}
          </TableHeadCell>
        )
      )
      .toArray();
  }

  tableRows(): Children {
    if (this.rows.length === 0) {
      return (
        <TableRow>
          <TableCell colspan={Object.keys(this.columns).length} style="text-align: center;">
            {this.__('Non sono presenti dati')}
          </TableCell>
        </TableRow>);
    }

    return this.rows.map((row: string[] | Model[], index) => {
      let cells = [];

      if (row instanceof Model) {
        // eslint-disable-next-line guard-for-in
        for (const attribute in this.columns) {
          cells.push(row.getAttribute(snakeCase(attribute)));
        }
      } else {
        cells = row;
      }

      return (
        <TableRow key={index}>
          {cells.map((cell: string, index_) => <TableCell key={index_}>{cell}</TableCell>)}
        </TableRow>
      );
    });
  }

  recordDialog() {
    return (
      <mwc-dialog id="add-record-dialog" heading={this.__('Aggiungi nuovo record')}>
        <form method="PUT">
          {(() => {
            const sections = collect(this.sections);

            return sections.map((section, index) => (
              <>
                <div id={section.id ?? index}>
                  <h2>{section.heading}</h2>
                  <LayoutGrid>
                    <Row>
                      {(() => {
                        const fields = collect(section.fields);

                        return fields.map((field, fieldIndex) => (
                          <Cell key={fieldIndex} columnspan={12 / (section.columns ?? 3)}>
                            <mwc-textfield {...field} id={field.id ?? fieldIndex}
                                           name={field.name ?? field.id ?? fieldIndex}/>
                          </Cell>))
                          .toArray();
                      })()}
                    </Row>
                  </LayoutGrid>
                </div>
                {index !== sections.keys()
                  .last() && <hr/>}
              </>
            ))
              .toArray();
          })()}
        </form>

        <mwc-button type="submit" slot="primaryAction">
          {this.__('Conferma')}
        </mwc-button>
        <mwc-button slot="secondaryAction" dialogAction="cancel">
          {this.__('Annulla')}
        </mwc-button>
      </mwc-dialog>
    );
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
        {this.recordDialog()}
        {this.dialogs}
      </>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    $('mwc-fab#add-record')
      .on('click', (clickEvent) => {
        const dialog = $(clickEvent.delegateTarget)
          .next('mwc-dialog#add-record-dialog');
        const form: JQuery = dialog.find('form');

        dialog.find('mwc-button[type="submit"]')
          .on('click', () => {
            form.trigger('submit');
          });

        form.attr('method', 'PUT')
          .off()
          .on('submit', async (event) => {
            event.preventDefault();

            // noinspection JSUnresolvedFunction
            if (form.isValid()) {
              const data = {};

              form.find('mwc-textfield, mwc-textarea')
                .each((index, field) => {
                  const key = this.saveModelWithSnakeCase ? snakeCase(field.id) : field.id;
                  data[key] = field.value;
                });

              // noinspection JSUnresolvedFunction
              const response = await this.model.create(data);
              if (response.getModelId()) {
                dialog.close();
                this.rows.push(response.getModel());
                m.redraw();
                await showSnackbar(this.__('Record creato'), 4000);
              }
            } else {
              await showSnackbar(this.__('Campi non validi. Controlla i dati inseriti'));
            }
          });

        dialog.get(0)
          .show();
      });
  }
}
