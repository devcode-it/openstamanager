import '@maicol07/mwc-layout-grid';
import '@material/mwc-dialog';
import '@material/mwc-fab';
import '@material/mwc-snackbar';

import type {Dialog as MWCDialog} from '@material/mwc-dialog';
import type {Cash} from 'cash-dom';
import collect, {type Collection} from 'collect.js';
import type {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import {sync as render} from 'mithril-node-render';

import {
  IModel,
  Model
} from '../../Models';
import type {
  FieldT,
  SelectT,
  TextAreaT,
  TextFieldT
} from '../../types';
import {getFormData, isFormValid, showSnackbar} from '../../utils';
import DataTable from '../DataTable/DataTable';
import TableCell from '../DataTable/TableCell';
import TableColumn from '../DataTable/TableColumn';
import TableRow from '../DataTable/TableRow';
import LoadingButton from '../LoadingButton';
import Mdi from '../Mdi';
import Page from '../Page';

export type ColumnT = {
  id?: string
  title: string
  type?: 'checkbox' | 'numeric'
  valueModifier?: (instance: IModel, property: string) => any
};
export type SectionT = {
  heading?: string
  columns?: number
  fields: Record<string, TextFieldT | TextAreaT | SelectT>
};
export type ColumnsT = Record<string, string | ColumnT>;
export type RowsT = Collection<IModel>;
export type SectionsT = Record<string, SectionT>;

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

  async oninit(vnode: Vnode) {
    super.oninit(vnode);
    // @ts-ignore
    const response = await this.model.with(this.model.relationships).get();
    const data = response.getData() as Model[];

    if (data.length > 0) {
      for (const record of data) {
        this.rows.put(record.getId(), record);
      }

      m.redraw();
    }
  }

  onupdate(vnode: VnodeDOM) {
    const rows: Cash = $('.mdc-data-table__row[data-model-id]');

    if (rows.length > 0) {
      rows.on('click', async (event: PointerEvent) => {
        const cell = event.target as HTMLElement;
        if (cell.tagName === 'MWC-CHECKBOX') {
          return;
        }

        await this.updateRecord($(cell).parent('tr').data('model-id') as number);
      });
    }
  }

  tableColumns(): JSX.Element[] {
    return collect(this.columns)
      // @ts-ignore
      .map((column: ColumnT | string, id: string) => (
        <TableColumn
          id={id}
          key={id}
          {...(typeof column === 'object' ? column : {})}
          sortable
          filterable
        >
          {typeof column === 'string' ? column : column.title}
        </TableColumn>
      ))
      .toArray();
  }

  tableRows(): Children {
    if (this.rows.isEmpty()) {
      return (
        <TableRow key="no-data">
          <TableCell
            colspan={collect(this.columns).count()}
            style="text-align: center;"
          >
            {__('Non sono presenti dati')}
          </TableCell>
        </TableRow>
      );
    }

    return this.rows
      .map((instance: IModel, index: string) => (
        <TableRow
          key={index}
          data-model-id={instance.getId()}
          style="cursor: pointer"
        >
          {collect(this.columns)
            .map((column, index_: string) => (
              <TableCell key={index_}>
                {this.getModelValue(instance, (column as ColumnT).id ?? index_)}
              </TableCell>
            ))
            .toArray()}
        </TableRow>
      ))
      .toArray();
  }

  async updateRecord(id: number) {
    // @ts-ignore
    const response = await this.model.with(this.model.relationships).find(id);
    const instance = response.getData() as IModel;
    const dialog = $('mwc-dialog#add-record-dialog');

    dialog
      // eslint-disable-next-line sonarjs/no-duplicate-string
      .find('text-field, text-area, material-select')
      .each(async (index, field) => {
        field.innerHTML = await this.getFieldBody(field as HTMLFormElement);
        (field as HTMLInputElement).value = this.getModelValue(instance, field.id) as string;
      });

    dialog
      .find('mwc-button#delete-button')
      .show()
      .on('click', () => {
        const confirmDialog = $('mwc-dialog#confirm-delete-record-dialog');
        const confirmButton = confirmDialog.find('mwc-button#confirm-button');
        const loading: Cash = confirmButton.find('mwc-circular-progress');
        confirmButton.on('click', async () => {
          loading.show();
          await instance.delete();
          // noinspection JSUnresolvedVariable
          this.rows.forget(instance.getId());
          m.redraw();
          await showSnackbar(__('Record eliminato!'), 4000);
        });
        loading.hide();
        (confirmDialog.get(0) as MWCDialog).show();
      });
    (dialog.get(0) as MWCDialog).show();
  }

  recordDialog() {
    return (
      <mwc-dialog
        id="add-record-dialog"
        className="record-dialog"
        heading={__('Aggiungi nuovo record')}
        // @ts-ignore
        style={`--mdc-dialog-max-width: ${this.recordDialogMaxWidth}`}
      >
        <form>
          <text-field
            id="id"
            name="id"
            // @ts-ignore
            style="display: none;"
            data-default-value=""
          />
          {(() => {
            const sections = collect(this.sections);
            return sections
              .map((section, id: string) => (
                <>
                  <div id={id}>
                    <h4 class="mdc-typography--overline">{section.heading}</h4>
                    <mwc-layout-grid>
                      {(() => {
                        const fields = collect(section.fields);
                        return fields
                          .map((field: TextFieldT | TextAreaT | SelectT, fieldIndex: string) => (
                            <mwc-layout-grid-cell
                              key={fieldIndex}
                              span={12 / (section.columns ?? 3)}
                            >
                              {m(
                                (field.elementType)
                                  ?? this.getElementFromType(field.type as string),
                                {
                                  ...field,
                                  id: field.id ?? fieldIndex,
                                  name: field.name ?? field.id ?? fieldIndex,
                                  'data-default-value':
                                    field.value ?? (field as SelectT).selected ?? ''
                                }
                              )}
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

        <LoadingButton
          type="submit"
          slot="primaryAction"
          label={__('Conferma')}
        />
        <mwc-button slot="secondaryAction" dialogAction="cancel">
          {__('Annulla')}
        </mwc-button>
        <mwc-button
          id="delete-button"
          slot="secondaryAction"
          label={__('Elimina')}
          style="--mdc-theme-primary: var(--mdc-theme-error, red); float: left; display: none;"
        >
          <Mdi icon="delete-outline" slot="icon" />
        </mwc-button>
      </mwc-dialog>
    );
  }

  deleteRecordDialog(): Children {
    return (
      <mwc-dialog id="confirm-delete-record-dialog">
        <p>{__('Sei sicuro di voler eliminare questo record?')}</p>
        <LoadingButton
          id="confirm-button"
          slot="primaryAction"
          label={__('Sì')}
        />
        <mwc-button
          slot="secondaryAction"
          dialogAction="discard"
          label={__('No')}
        />
      </mwc-dialog>
    );
  }

  view(vnode: Vnode) {
    return (
      <>
        <h2>{this.title}</h2>
        <DataTable checkable paginated>
          {this.tableColumns()}
          {this.tableRows()}
        </DataTable>

        <mwc-fab id="add-record" label={__('Aggiungi')} className="sticky">
          <Mdi icon="plus" slot="icon" />
        </mwc-fab>
        {this.recordDialog()}
        {this.deleteRecordDialog()}
        {this.dialogs}
      </>
    );
  }

  oncreate(vnode: VnodeDOM) {
    super.oncreate(vnode);

    const fab: Cash = $('mwc-fab#add-record');
    const dialog: Cash = fab.next('mwc-dialog#add-record-dialog');
    const form: Cash = dialog.find('form');

    // Open "New record" dialog
    fab.on('click', this.openNewRecordDialog.bind(this, form, dialog));

    const button = dialog.find('mwc-button[type="submit"]');
    button.on('click', () => form.trigger('submit'));
    form.on('submit', this.submitForm.bind(this, button, dialog, form));
  }

  openNewRecordDialog(form: Cash, dialog: Cash) {
    form
      .find('text-field, text-area, material-select')
      .each(async (index, field) => {
        field.innerHTML = await this.getFieldBody(field as HTMLFormElement);
        (field as HTMLInputElement).value = $(field)
          .data('default-value') as string;
      });
    dialog.find('mwc-button[type="submit"] mwc-circular-progress').hide();
    dialog.find('mwc-button#delete-button').hide();
    const dialogElement: HTMLElement & Partial<MWCDialog> | undefined = dialog.get(0);
    if (dialogElement) {
      (dialogElement as MWCDialog).show();
    }
  }

  async submitForm(button: Cash, dialog: Cash, form: Cash, event: SubmitEvent) {
    event.preventDefault();
    const loading: Cash = button.find('mwc-circular-progress');
    loading.show();

    if (isFormValid(form)) {
      const data = collect(getFormData(form));
      // @ts-ignore
      // eslint-disable-next-line new-cap
      const instance = this.rows.get(data.get('id'), new this.model() as IModel) as IModel;

      const modelId = await this.setter(instance, data);

      if (modelId) {
        // @ts-ignore
        const newResponse = await this.model.with(this.model.relationships)
          .find(modelId);
        const model = newResponse.getData() as IModel;

        const dialogElement = dialog.get(0);
        if (dialogElement) {
          (dialogElement as MWCDialog).close();
        }

        this.rows.put(model.getId(), model);
        loading.hide();
        m.redraw();
        await showSnackbar(__('Record salvato'), 4000);
      }
    } else {
      loading.hide();
      await showSnackbar(__('Campi non validi. Controlla i dati inseriti'));
    }
  }

  async setter(model: IModel, data: Collection<File | string>) {
    const filtered = data
      .filter((item: any, id: string) => this.fieldsPrecedence.includes(id));

    // @ts-ignore
    (filtered.isEmpty() ? filtered : data).each((value: string, id: string) => {
      model[id] = value;
    });

    const response = await model.save();
    return response.getModelId();
  }

  getModelValue(model: IModel, field: string, raw = false): any {
    const column = this.columns[field];
    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
    let value: any = model[field];

    if (typeof column === 'object' && column.valueModifier) {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
      value = column.valueModifier(model, field);
    }

    return (value || raw) ? value : '';
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

  async getFieldBody(field: HTMLFormElement) {
    const list = [];

    switch (field.type ?? field.getAttribute('type')) {
      case 'select':
        // eslint-disable-next-line no-case-declarations
        const section = collect(this.sections).first((s) => field.id in s.fields);
        // eslint-disable-next-line no-case-declarations
        let {options} = section.fields[field.id] as SelectT;
        if (options instanceof Promise) {
          options = await options;
        }

        if (options) {
          for (const option of options) {
            list.push(render(
              <mwc-list-item key={option.value} value={option.value}>
                {option.label}
              </mwc-list-item>
            ));
          }
        }

        break;

      case 'checkbox':
        return '';

      case 'radio':
        return '';

      default:
    }

    if (field.icon) {
      list.push(render(<Mdi icon={(field as FieldT).icon} slot="icon"/>));
    }

    return list.join('');
  }
}
