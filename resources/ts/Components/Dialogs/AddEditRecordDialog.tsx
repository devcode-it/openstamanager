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
  protected abstract formState: Map<string, Stream<unknown>>;
  protected abstract modelType: Class<M>;
  // Recommended: <= 3
  protected numberOfColumns: number = 3;
  protected record!: M;

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
  }

  fillForm() {
    for (const [key, value] of this.formState) {
      value(this.record.getAttribute(key) ?? value());
    }
  }

  contents(): Children {
    return (
      <>
        <h2 slot="headline">{this.record.isNew() ? __('Nuovo record') : __('Modifica record')}</h2>
        {this.form()}
        {this.afterForm().toArray()}
      </>
    );
  }

  form(): Children {
    return (
      <Form state={this.formState} onsubmit={this.onFormSubmit.bind(this)}>
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
    this.close('cancel');
  }

  async onFormSubmit() {
    if (isFormValid(this.formElement!) && await this.save()) {
      this.close();
    }
  }

  afterForm(): VnodeCollection {
    return collect<VnodeCollectionItem>({
      cancelButton: (
        <md-text-button slot="footer" onclick={this.onCancelButtonClicked.bind(this)}>
          {__('Annulla')}
        </md-text-button>
      ),
      saveButton: (
        <md-text-button type="submit" slot="footer" onclick={this.onSaveButtonClicked.bind(this)}>
          {__('Salva')}
          <MdIcon icon={mdiFloppy} slot="icon"/>
        </md-text-button>
      )
    });
  }

  onSaveButtonClicked(): void {
    this.formElement?.requestSubmit();
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

  protected static createFormState(entries: Record<string, Stream<any>>): Map<string, Stream<unknown>> {
    return new Map(Object.entries(entries));
  }

  get modelAttributesFromFormState(): Record<string, unknown> {
    const state: Record<string, unknown> = {};
    for (const [key, value] of this.formState) {
      state[key] = value();
    }
    return state;
  }
}
