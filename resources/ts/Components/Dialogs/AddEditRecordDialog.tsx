import {mdiFloppy} from '@mdi/js';
import RecordDialog, {RecordDialogAttributes} from '@osm/Components/Dialogs/RecordDialog';
import MdIcon from '@osm/Components/MdIcon';
import Model from '@osm/Models/Model';
import {
  VnodeCollection,
  VnodeCollectionItem
} from '@osm/typings/jsx';
import {JSONAPI} from '@osm/typings/request';
import {
  isFormValid,
  isVnode,
  showSnackbar
} from '@osm/utils/misc';
import collect, {Collection} from 'collect.js';
import {SaveResponse} from 'coloquent';
import {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import Stream from 'mithril/stream';
import {Form} from 'mithril-utilities';
import {Class} from 'type-fest';

export default abstract class AddEditRecordDialog<M extends Model<any, any>> extends RecordDialog<M> {
  // eslint-disable-next-line unicorn/no-null
  protected formElement: HTMLFormElement | null = null;
  protected abstract formState: Map<string, Stream<any>>;
  protected abstract modelType: Class<M>;
  // Recommended: <= 3
  protected numberOfColumns: number = 3;
  protected record!: M;
  protected formId?: string;

  oninit(vnode: Vnode<RecordDialogAttributes<M>, this>) {
    super.oninit(vnode);

    if (!this.record) {
      this.record = new this.modelType();
    }

    this.fillForm();
  }

  oncreate(vnode: VnodeDOM<RecordDialogAttributes<M>, this>) {
    super.oncreate(vnode);

    this.formElement = this.element.querySelector('form');
    this.element.querySelector(`#saveBtn${this.formId}`)?.setAttribute('form', this.formId!);
  }

  fillForm() {
    for (const [key, value] of this.formState) {
      value(this.record.getAttribute(key) ?? value());
    }
  }

  contents(): Children {
    return (
      <>
        {this.form()}
      </>
    );
  }

  headline() {
    return <span slot="headline">{this.record.isNew() ? __('Nuovo record') : __('Modifica record')}</span>;
  }

  form(): Children {
    this.formId ??= `form-${Date.now()}`;
    return (
      <Form id={this.formId} state={this.formState} onsubmit={this.onFormSubmit.bind(this)} additionalElementsSelector="md-filled-select,md-outlined-select">
        {this.formContents()}
      </Form>
    );
  }

  formContents(): Children {
    return (
      <md-layout-grid>
        {...this.formFields().values().all()}
      </md-layout-grid>
    );
  }

  protected formFields(): Collection<Children> {
    return this.fields().map((field, key: string) => {
      // TODO: Remove this cast when new collection library is done
      if (isVnode<{name?: string, 'grid-span'?: number}>(field)) {
        field.attrs.name ??= key;
        field.attrs['grid-span'] ??= Math.floor(12 / this.numberOfColumns);
        field.key = key;
      }
      return field;
    });
  }

  abstract fields(): Collection<Children>;

  onCancelButtonClicked(): void {
    void this.close('cancel');
  }

  async onFormSubmit() {
    if (isFormValid(this.formElement!) && await this.save()) {
      void this.close();
    }
  }

  actions(): VnodeCollection {
    return collect<VnodeCollectionItem>({
      cancelButton: (
        <md-text-button onclick={this.onCancelButtonClicked.bind(this)}>
          {__('Annulla')}
        </md-text-button>
      ),
      saveButton: (
        <md-text-button id={`saveBtn${this.formId}`} type="submit">
          {__('Salva')}
          <MdIcon icon={mdiFloppy} slot="icon"/>
        </md-text-button>
      )
    });
  }

  async save(): Promise<boolean> {
    this.record.setAttributes(this.modelAttributesFromFormState);
    try {
      const response = await this.record.save();
      this.afterSave(response);
      return response.getModelId() !== undefined;
    } catch (error) {
      this.onSaveError(error as JSONAPI.RequestError);
      return false;
    }
  }

  afterSave(response: SaveResponse<M>): void {
    const responseModel = response.getModel() as M;
    if (responseModel !== undefined) {
      this.record = responseModel;
      void showSnackbar(__('Record salvato con successo'));
    }
  }

  onSaveError(error: JSONAPI.RequestError): void {
    const message = error.response.errors.map((error_) => error_.detail).join('; ');
    void showSnackbar(message, false);
  }

  protected static createFormState<EK extends string, EV extends Stream<any>>(entries: Record<EK, EV>): Map<EK, EV> {
    return new Map<EK, EV>(Object.entries(entries) as [EK, EV][]);
  }

  get modelAttributesFromFormState(): Record<string, unknown> {
    const state: Record<string, unknown> = {};
    for (const [key, value] of this.formState) {
      state[key] = value();
    }
    return state;
  }
}
