import '@maicol07/mwc-layout-grid';
import '@material/mwc-dialog';
import '@material/mwc-fab';
import '@material/mwc-snackbar';

import type {Cash} from 'cash-dom/dist/cash';
import collect, {Collection} from 'collect.js';
import {Children} from 'mithril';

import {Model} from '../../Models';
import type {
  SelectT,
  TextAreaT,
  TextFieldT
} from '../../types';
import {
  isFormValid,
  showSnackbar
} from '../../utils';
import type {
  TextArea,
  TextField
} from '../../WebComponents';
import DataTable from '../DataTable/DataTable.jsx';
import TableCell from '../DataTable/TableCell.jsx';
import TableColumn from '../DataTable/TableColumn.jsx';
import TableRow from '../DataTable/TableRow.jsx';
import LoadingButton from '../LoadingButton.jsx';
import Mdi from '../Mdi.jsx';
import Page from '../Page.jsx';


export type ColumnT = {
  id?: string,
  title: string,
  type?: 'checkbox' | 'numeric',
  valueModifier?: (instance: Model, prop: string) => any
}

export type SectionT = {
  id?: string,
  heading?: string,
  columns?: number,
  fields: TextFieldT[] | TextAreaT | SelectT[] | { [string]: TextFieldT | TextAreaT | SelectT }
};

export type ColumnsT = { [string]: [string] | ColumnT };
export type RowsT = Collection<Model>;
export type SectionsT = { [string]: SectionT } | SectionT[];

/**
 * @abstract
 */
export class RecordsPage extends Page {
  columns: ColumnsT;
  rows: RowsT = collect({});

  sections: SectionsT;

  dialogs: Children[];

  recordDialogMaxWidth: string | number = 'auto';

  model: typeof Model;

  /**
   * What fields should take precedence when saving the record
   */
  fieldsPrecedence: string[] = [];

  async oninit(vnode) {
    super.oninit(vnode);
    const response = await this.model.all();
    const data: Model[] = response.getData();

    if (data.length > 0) {
      for (const record of data) {
        this.rows.put(record.id, record);
      }
      m.redraw();
    }
  }

  onupdate(vnode) {
    const rows: Cash = $('.mdc-data-table__row[data-model-id]');
    if (rows.length > 0) {
      rows.on(
        'click',
        async (event: PointerEvent) => {
          if (event.target.tagName === 'MWC-CHECKBOX') {
            return;
          }
          await this.updateRecord($(event.target)
            .parent('tr')
            .data('model-id'));
        }
      );
    }
  }

  tableColumns(): Children {
    return collect(this.columns)
      .map(
        (column: ColumnT | string, id: string) => (
          <TableColumn id={id} key={id} {...((typeof column === 'object') ? column : {})} sortable
                       filterable>
            {typeof column === 'string' ? column : column.title}
          </TableColumn>
        )
      )
      .toArray();
  }

  tableRows(): Children {
    if (this.rows.isEmpty()) {
      return (
        <TableRow key="no-data">
          <TableCell colspan={collect(this.columns)
            .count()} style="text-align: center;">
            {__('Non sono presenti dati')}
          </TableCell>
        </TableRow>);
    }

    return this.rows.map((instance: Model, index) => (
      <TableRow key={index} data-model-id={instance.id} style="cursor: pointer">
        {collect(this.columns).map((column, index_) => (
          <TableCell key={index_}>
            {this.getModelValue(instance, column.id ?? index_)}
          </TableCell>
        )).toArray()}
      </TableRow>
    )).toArray();
  }

  async updateRecord(id: number) {
    const response = await this.model.find(id);
    const instance = response.getData();
    const dialog = $('mwc-dialog#add-record-dialog');

    // eslint-disable-next-line sonarjs/no-duplicate-string
    dialog.find('text-field, text-area, material-select')
      .each(
        (index, field: TextFieldT | TextAreaT | SelectT) => this.getModelValue(instance, field.id)
      );

    dialog.find('mwc-button#delete-button')
      .show()
      .on('click', () => {
        const confirmDialog = $('mwc-dialog#confirm-delete-record-dialog');
        const confirmButton = confirmDialog.find('mwc-button#confirm-button');
        const loading: Cash = confirmButton.find('mwc-circular-progress');

        confirmButton.on('click', async () => {
          loading.show();

          await instance.delete();

          // noinspection JSUnresolvedVariable
          this.rows.forget(instance.id);
          m.redraw();
          await showSnackbar(__('Record eliminato!'), 4000);
        });

        loading.hide();

        confirmDialog.get(0)
          .show();
      });

    dialog.get(0)
      .show();
  }

  recordDialog() {
    return (
      <mwc-dialog id="add-record-dialog" class="record-dialog"
                  heading={__('Aggiungi nuovo record')}
                  style={`--mdc-dialog-max-width: ${this.recordDialogMaxWidth}`}>
        <form>
          <text-field id="id" name="id" style="display: none;" data-default-value=""/>
          {(() => {
            const sections = collect(this.sections);

            return sections.map((section, index) => (
              <>
                <div id={section.id ?? index}>
                  <h4 class="mdc-typography--overline">{section.heading}</h4>
                  <mwc-layout-grid>
                    {(() => {
                      const fields = collect(section.fields);

                      return fields.map((field, fieldIndex) => (
                        <mwc-layout-grid-cell key={fieldIndex}
                                              span={12 / (section.columns ?? 3)}>
                          {m(field.elementType ?? this.getElementFromType(field.type), {
                            ...field,
                            id: field.id ?? fieldIndex,
                            name: field.name ?? field.id ?? fieldIndex,
                            'data-default-value': field.value ?? (field.selected ?? '')
                          }, this.getFieldBody(field))}
                        </mwc-layout-grid-cell>
                      ))
                        .toArray();
                    })()}
                  </mwc-layout-grid>
                </div>
              </>
            ))
              .toArray();
          })()}
        </form>

        <LoadingButton type="submit" slot="primaryAction" label={__('Conferma')}/>
        <mwc-button slot="secondaryAction" dialogAction="cancel">
          {__('Annulla')}
        </mwc-button>
        <mwc-button id="delete-button" slot="secondaryAction" label={__('Elimina')}
                    style="--mdc-theme-primary: var(--mdc-theme-error, red); float: left; display: none;">
          <Mdi icon="delete-outline" slot="icon"/>
        </mwc-button>
      </mwc-dialog>
    );
  }

  deleteRecordDialog(): Children {
    return (
      <mwc-dialog id="confirm-delete-record-dialog">
        <p>{__('Sei sicuro di voler eliminare questo record?')}</p>
        <LoadingButton id="confirm-button" slot="primaryAction" label={__('Sì')}/>
        <mwc-button slot="secondaryAction" dialogAction="discard" label={__('No')}/>
      </mwc-dialog>
    );
  }

  view(vnode) {
    return (
      <>
        <h2>{this.title}</h2>
        <DataTable checkable paginated>
          {this.tableColumns()}
          {this.tableRows()}
        </DataTable>

        <mwc-fab id="add-record" label={__('Aggiungi')} class="sticky">
          <Mdi icon="plus" slot="icon"/>
        </mwc-fab>
        {this.recordDialog()}
        {this.deleteRecordDialog()}
        {this.dialogs}
      </>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    const fab: Cash = $('mwc-fab#add-record');
    const dialog: Cash = fab.next('mwc-dialog#add-record-dialog');
    const form: Cash = dialog.find('form');

    fab.on('click', () => {
      form.find('text-field, text-area, material-select')
        .each((index, field) => {
          field.value = $(field)
            .data('default-value');
        });

      dialog.find('mwc-button[type="submit"] mwc-circular-progress')
        .hide();

      dialog.find('mwc-button#delete-button')
        .hide();

      dialog.get(0)
        .show();
    });

    const button = dialog.find('mwc-button[type="submit"]');
    button.on('click', () => {
      form.trigger('submit');
    });
    const loading: Cash = button.find('mwc-circular-progress');

    form.on('submit', async (event) => {
      event.preventDefault();
      loading.show();

      if (isFormValid(form)) {
        // eslint-disable-next-line new-cap
        const instance: Model = new this.model();

        const fields = form.find('text-field, text-area, material-select');
        for (const fieldName of this.fieldsPrecedence) {
          const field = fields.find(`#${fieldName}`);
          instance[field.attr('id')] = field.val();
        }

        fields.each((index, field: TextField | TextArea) => {
          instance[field.id] = field.value;
        });

        const response = await instance.save();
        if (response.getModelId()) {
          dialog.get(0)
            .close();

          const model = response.getModel();
          this.rows.put(model.id, model);

          m.redraw();
          await showSnackbar(__('Record salvato'), 4000);
        }
      } else {
        loading.hide();
        await showSnackbar(__('Campi non validi. Controlla i dati inseriti'));
      }
    });
  }

  getModelValue(model: Model, field: string): any {
    const column = this.columns[field]
    let value = model[field];

    if (typeof column === 'object' && column.valueModifier) {
      value = column.valueModifier(model, field);
    }

    return value;
  }

  getElementFromType(type: string) {
    switch (type) {
      case 'text':
        return 'text-field';
      case 'textarea':
        return 'text-area';
      case 'select':
        return 'material-select';
      /* Case 'checkbox':
      case 'radio':
        return Radio; */
      default:
        return 'text-field';
    }
  }

  getFieldBody(field: TextFieldT | TextAreaT | SelectT) {
    switch (field.type) {
      case 'select':
        return (
          <>
            {field.options.map((option: { value: string, label: string }) => {
              return (
                <mwc-list-item key={option} value={option.value}>{option.label}</mwc-list-item>
              );
            })}
          </>
        );
      case 'checkbox':
        return '';
      case 'radio':
        return '';
      default:
        return '';
    }
  }
}
