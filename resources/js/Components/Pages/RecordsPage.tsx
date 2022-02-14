/* eslint-disable no-await-in-loop */
import '@maicol07/mwc-layout-grid';
import '@material/mwc-dialog';
import '@material/mwc-fab';
import '@material/mwc-snackbar';

import type {Button as MWCButton} from '@material/mwc-button';
import type {Dialog as MWCDialog} from '@material/mwc-dialog';
import type {Cash} from 'cash-dom';
import collect, {type Collection} from 'collect.js';
import {
  ToManyRelation,
  ToOneRelation
} from 'coloquent';
import {capitalize} from 'lodash-es';
import type {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import {sync as render} from 'mithril-node-render';

import {
  IModel,
  InstantiableModel,
  Model
} from '../../Models';
import type {
  FieldT,
  SelectOptionsT,
  SelectT,
  TextAreaT,
  TextFieldT
} from '../../typings';
import {JSONAPI} from '../../typings';
import {
  getFormData,
  isFormValid,
  showSnackbar
} from '../../utils';
import type {Select} from '../../WebComponents';
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
  valueModifier?: (value: any, field: string, model: IModel) => any
};
export type SectionT = {
  heading?: string
  columns?: number
  fields: Record<string, TextFieldT | TextAreaT | SelectT>
};
export type ColumnsT = Record<string, string | ColumnT>;
export type RowsT = Collection<IModel>;
export type SectionsT = Record<string, SectionT>;

const FIELDS: string = 'text-field, text-area, material-select';

// TODO: Refactor con l'utilizzo di sottocomponenti (es. per le dialog)

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
  /** A list of relations to delete when deleting the record */
  relationsToDelete: string[] = [];

  /**
   * What fields should take precedence when saving the record
   */
  fieldsPrecedence: string[] = [];

  async oninit(vnode: Vnode) {
    super.oninit(vnode);
    // @ts-ignore
    const response = await this.model.with(this.model.relationships)
      .get();
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

        await this.updateRecord($(cell)
          .parent('tr')
          .data('model-id') as number);
      });
    }

    const observer = new IntersectionObserver((entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          const select = entry.target as Select;
          if (select.fixedMenuPosition) {
            select.addEventListener('opened', () => select.style.setProperty('--mdc-menu-min-width', `${select.offsetWidth}px`));
          }
        }
      }
    }, {threshold: [0]});

    $('material-select')
      .each((index, element) => {
        observer.observe(element);
      });
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
            .map((column, index_: string) => {
              const columnId = (column as ColumnT).id ?? index_;

              this.getModelValue(instance, columnId, true).then((value: string) => {
                $(`td#${columnId}-${index}`).text(value);
              }).catch(() => {}).finally(() => {});

              return <TableCell id={`${columnId}-${index}`} key={index_}/>;
            })
            .toArray()}
        </TableRow>
      ))
      .toArray();
  }

  async updateRecord(id: number) {
    // @ts-ignore
    const response = await this.model.with(this.model.relationships).find(id);
    const instance = response.getData() as IModel;
    const dialog: MWCDialog | null = document.querySelector('mwc-dialog#add-record-dialog');

    if (dialog) {
      for (const field of dialog.querySelectorAll(FIELDS)) {
        const value = await this.getModelValue(instance, field.id) as string;

        field.innerHTML = await this.getFieldBody(field as HTMLFormElement, value);

        (field as HTMLInputElement).value = value;
      }

      $(dialog)
        .find('mwc-button#delete-button')
        .show()
        .on('click', this.openDeleteRecordDialog.bind(this, dialog, instance));

      dialog.show();
    }
  }

  recordDialog(): Children {
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
                                    field.value ?? (field as SelectT).selected ?? '',
                                  fixedMenuPosition: field.type === 'select'
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
      // eslint-disable-next-line unicorn/no-array-callback-reference
      .find(FIELDS)
      .each(async (index, field) => {
        field.innerHTML = await this.getFieldBody(field as HTMLFormElement);
        (field as HTMLInputElement).value = $(field)
          .data('default-value') as string;
      });
    dialog.find('mwc-button[type="submit"] mwc-circular-progress')
      .hide();
    dialog.find('mwc-button#delete-button')
      .hide();
    const dialogElement: HTMLElement & Partial<MWCDialog> | undefined = dialog.get(0);
    if (dialogElement) {
      (dialogElement as MWCDialog).show();
    }
  }

  openDeleteRecordDialog(recordDialog: MWCDialog, instance: IModel) {
    const dialog: MWCDialog | null = document.querySelector('mwc-dialog#confirm-delete-record-dialog');
    if (dialog) {
      dialog.show();
      const confirmButton: MWCButton | null = dialog.querySelector('mwc-button#confirm-button');
      // eslint-disable-next-line @typescript-eslint/no-misused-promises
      confirmButton?.addEventListener('click', this.deleteRecord.bind(this, recordDialog, dialog, confirmButton, instance));
    }
  }

  async submitForm(button: Cash, dialog: Cash, form: Cash, event: SubmitEvent) {
    event.preventDefault();
    const loading: Cash = button.find('mwc-circular-progress');
    loading.show();

    if (isFormValid(form)) {
      const data = collect(getFormData(form));
      // @ts-ignore
      const instance = this.rows.get(data.get('id'), new this.model() as IModel) as IModel;

      const modelId = await this.setter(instance, data.except(['id']));

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
        m.redraw();
        await showSnackbar(__('Record salvato'), 4000);
      }
    } else {
      await showSnackbar(__('Campi non validi. Controlla i dati inseriti'));
    }

    loading.hide();
  }

  // eslint-disable-next-line consistent-return
  async setter(model: IModel, data: Collection<File | string>) {
    const firstFields = data.only(this.fieldsPrecedence);
    const fields = data.except(this.fieldsPrecedence);

    firstFields.each((currentItem, key) => {
      fields.put(key, currentItem);
    });

    const relations = await this.loadRelations(model, data);

    await this.setFields(model, relations, data);

    try {
      // Save relations (only those that changed)
      const relationsToSave = data.filter((value: any, key: string) => key.includes(':'))
        .keys()
        .map((item) => item.split(':')[0])
        .unique()
        .all();

      for (const relation of relationsToSave) {
        const response = await relations[relation].save();
        relations[relation] = response.getModel() as IModel;
      }

      for (const [relation, relatedModel] of Object.entries(relations)) {
        model.setRelation(relation, relatedModel);
      }

      const response = await model.save();
      return response.getModelId();
    } catch (error) {
      const {errors} = (error as JSONAPI.RequestError).response.data;
      const errorMessage = errors.map((error_) => error_.detail)
        .join(';\n');
      void showSnackbar(__('Errore durante il salvataggio: :error', {error: errorMessage}), false);
    }
  }

  // eslint-disable-next-line @typescript-eslint/require-await
  async setFields(
    model: IModel,
    relations: Record<string, IModel>,
    data: Collection<File | string>
  ) {
    for (const [field, value] of Object.entries(data.except(Object.keys(relations))
      .all())) {
      const fieldValue = value !== '' ? value : undefined;
      if (field.includes(':')) {
        const [relation, fieldName]: (string | undefined)[] = field.split(':');
        const relationModel: IModel = relation in relations
          ? relations[relation]
          : await this.getRelation(model, relation, true) as IModel;

        if (relationModel) {
          relationModel[fieldName] = fieldValue;
          relations[relation] = relationModel;
        }
      } else {
        model[field] = fieldValue;
      }
    }
  }

  async deleteRecord(
    recordDialog: MWCDialog,
    dialog: MWCDialog,
    button: MWCButton,
    instance: IModel
  ) {
    const loading = $(button.querySelector('mwc-circular-progress'));
    loading.show();

    try {
      for (const relation of this.relationsToDelete) {
        const relatedModel = await this.getRelation(instance, relation, false);
        if (relatedModel) {
          await relatedModel.delete();
        }
      }
      await instance.delete();
      this.rows.forget(instance.getId());
      m.redraw();
      void showSnackbar(__('Record eliminato!'), 4000);
      dialog.close();
      recordDialog.close();
    } catch (error) {
      const {errors} = (error as JSONAPI.RequestError).response.data;
      const errorMessage = errors.map((error_) => error_.detail)
        .join(';\n');
      void showSnackbar(__('Errore durante l\'eliminazione: :error', {error: errorMessage}), false);
    }

    loading.hide();
  }

  async loadRelations(model: IModel, data: Collection<File | string>) {
    const relations: Record<string, IModel> = {};
    const proto = (Object.getPrototypeOf(model) as Model).constructor as typeof Model;

    const relationsData = data.filter(
      (value: any, field: string) => proto.relationships.includes(field)
    );

    for (const [field, value] of Object.entries(relationsData.all())) {
      relations[field] = await this.getRelation(model, field, false, Number(value)) as IModel;
    }

    return relations;
  }

  async getRelation(
    model: IModel,
    relation: string,
    createIfNotExists: boolean = false,
    id?: number
  ) {
    const getter = `get${capitalize(relation)}`;
    const relationModel: IModel | undefined = (typeof model[getter] === 'function'
      ? (model[getter] as Function)()
      : model.getRelation(relation)) as IModel;

    if (relationModel) {
      return relationModel;
    }

    const relationship = (model[relation] as Function)() as
      ToOneRelation<IModel> | ToManyRelation<IModel>;
    const RelationshipModel = relationship.getType() as typeof Model | InstantiableModel;

    if (id) {
      // @ts-ignore
      const response = await (RelationshipModel as typeof Model).find(id);
      return response.getData() as IModel;
    }

    return createIfNotExists ? new (RelationshipModel as InstantiableModel)() : undefined;
  }

  async getModelValue(
    model: IModel,
    field: string,
    useValueModifier = false,
    sections = this.sections,
    raw = false
  ): Promise<any> {
    const column = this.columns[field];
    const sectionField = collect(sections)
      .pluck(`fields.${field}`)
      .first() as SelectT | null;
    let value: unknown;
    if (field.includes(':') || sectionField?.relationship) {
      let relation;
      let fieldName = '';

      if (field.includes(':')) {
        [relation, fieldName] = field.split(':');
        const blankModel = await this.getRelation(model, relation);
        const relatedModel = await blankModel?.fresh();
        value = relatedModel?.[fieldName];
      } else {
        if (Array.isArray(sectionField?.relationship)) {
          fieldName = sectionField?.relationship[1] as string;
        }
        relation = field;
        const relatedModel = await this.getRelation(model, relation);
        value = relatedModel?.getId();
      }
    } else {
      value = model[field];
    }

    if (useValueModifier && typeof column === 'object' && column.valueModifier) {
      value = column.valueModifier(value, field, model);
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

  async getFieldBody(field: HTMLFormElement & FieldT, value?: string) {
    const list = [];

    switch (field.type ?? field.getAttribute('type')) {
      case 'select': {
        const section = collect(this.sections)
          // (temporary) .first((s) => field.id in s.fields);
          .filter((s) => field.id in s.fields)
          .first();

        const select = section.fields[field.id] as SelectT;
        let {options} = select;
        const {relationship} = select;

        if (Array.isArray(relationship) && relationship.length === 2) {
          options = this.getModelSelectOptions(relationship[0], relationship[1]);
        }

        if (options instanceof Promise) {
          options = await options;
        }

        if (options) {
          for (const option of options) {
            list.push(render(
              <mwc-list-item key={option.value} value={option.value}
                             selected={option.value === value} activated={option.value === value}>
                {option.label}
              </mwc-list-item>
            ));
          }
        }

        break;
      }
      case 'checkbox':
        return '';
      case 'radio':
        return '';
      default:
    }

    // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
    const {icon} = field;
    if (icon) {
      list.push(render(<Mdi icon={icon} slot="icon"/>));
    }

    return list.join('');
  }

  async getModelSelectOptions(
    model: typeof Model,
    labelAttribute: string
  ): Promise<SelectOptionsT> {
    const response = await model.all();
    const categories = response.getData();

    return categories.map((instance: IModel) => ({
      value: instance.getId() as string,
      label: instance[labelAttribute] as string
    }));
  }
}
