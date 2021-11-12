import '@maicol07/mwc-layout-grid';
import '@material/mwc-dialog';
import '@material/mwc-fab';
import '@material/mwc-snackbar';

import type {
  TextFieldInputMode,
  TextFieldType
} from '@material/mwc-textfield/mwc-textfield-base';
import type {Cash} from 'cash-dom/dist/cash';
import collect, {Collection} from 'collect.js';
import {Children} from 'mithril';

import {Model} from '../../Models';
import {
  isFormValid,
  showSnackbar
} from '../../utils';
import type {
  TextArea,
  TextField
} from '../../WebComponents';
import DataTable from '../DataTable/DataTable.jsx';
import TableBody from '../DataTable/TableBody.jsx';
import TableCell from '../DataTable/TableCell.jsx';
import TableHead from '../DataTable/TableHead.jsx';
import TableHeadCell from '../DataTable/TableHeadCell.jsx';
import TableHeadRow from '../DataTable/TableHeadRow.jsx';
import TableRow from '../DataTable/TableRow.jsx';
import LoadingButton from '../LoadingButton.jsx';
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
  rows: Collection<Model> = collect({});

  sections: { [string]: SectionT } | SectionT[];

  dialogs: Children[];

  recordDialogMaxWidth: string | number = '75%';

  model: Model;

  async oninit(vnode) {
    // noinspection JSUnresolvedFunction
    vnode.state.data = (await this.model.all()).getData();
    if (vnode.state.data) {
      for (const record of vnode.state.data) {
        this.rows.put(record.id, record);
      }
      m.redraw();
    }
  }

  async onupdate(vnode) {
    const rows = $('.mdc-data-table__row[data-model-id]');
    if (rows.length > 0) {
      rows.on(
        'click',
        (event: PointerEvent) => {
          this.updateRecord($(event.target)
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
          <TableHeadCell id={id} key={id} {...((typeof column === 'object') ? column : {})}>
            {typeof column === 'string' ? column : column.title}
          </TableHeadCell>
        )
      )
      .toArray();
  }

  tableRows(): Children {
    if (this.rows.isEmpty()) {
      return (
        <TableRow>
          <TableCell colspan={collect(this.columns)
            .count()} style="text-align: center;">
            {__('Non sono presenti dati')}
          </TableCell>
        </TableRow>);
    }

    return this.rows.map((row: Model, index) => {
      const cells = [];

      // eslint-disable-next-line guard-for-in
      for (const attribute in this.columns) {
        cells.push(row[attribute]);
      }

      return (
        <TableRow key={index} data-model-id={row.id} style="cursor: pointer">
          {cells.map((cell: string, index_) => <TableCell key={index_}>{cell}</TableCell>)}
        </TableRow>
      );
    })
      .toArray();
  }

  async updateRecord(id: number) {
    // noinspection JSUnresolvedFunction
    const instance: Model = (await this.model.find(id)).getData();
    const dialog = $('mwc-dialog#add-record-dialog');

    // eslint-disable-next-line sonarjs/no-duplicate-string
    dialog.find('text-field, text-area')
      .each((index, field: TextField | TextArea) => {
        field.value = instance[field.id];
      });

    dialog.find('mwc-button#delete-button')
      .show()
      .on('click', () => {
        const confirmDialog = $('mwc-dialog#confirm-delete-record-dialog');
        const confirmButton = confirmDialog.find('mwc-button#confirm-button');
        const loading: Cash = confirmButton.find('mwc-circular-progress');

        confirmButton.on('click', async () => {
          loading.show();

          await instance.delete();

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
        <form method="PUT">
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
                          <mwc-layout-grid-cell key={fieldIndex} span={12 / (section.columns ?? 3)}>
                            <text-field {...field} id={field.id ?? fieldIndex}
                                        name={field.name ?? field.id ?? fieldIndex}
                                        data-default-value={field.value ?? ''}/>
                          </mwc-layout-grid-cell>))
                          .toArray();
                      })()}
                  </mwc-layout-grid>
                </div>
                {index !== sections.keys()
                  .last() && <hr/>}
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
      form.find('text-field, text-area')
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

        form.find('text-field, text-area')
          .each((index, field: TextField | TextArea) => {
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
}
