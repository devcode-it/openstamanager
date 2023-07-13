import Model from '@osm/Models/Model';
import {showSnackbar} from '@osm/utils/misc';
import {
  Children,
  Vnode
} from 'mithril';
import {RequestError} from 'mithril-utilities';

import RecordDialog, {RecordDialogAttributes} from './RecordDialog';

export interface DeleteRecordDialogAttributes<M extends Model<any, any>> extends RecordDialogAttributes<M> {
  records: M | M[];
}

export default class DeleteRecordDialog<M extends Model<any, any>, A extends DeleteRecordDialogAttributes<M> = DeleteRecordDialogAttributes<M>> extends RecordDialog<M, A> {
  records!: M[];

  oninit(vnode: Vnode<A, this>) {
    super.oninit(vnode);
    this.records = Array.isArray(vnode.attrs.records) ? vnode.attrs.records : [vnode.attrs.records];
  }

  contents(vnode: Vnode<A, this>): Children {
    const text = this.records.length > 1
      ? __('Sei sicuro di voler eliminare questi :count record?', {count: this.records.length})
      : __('Sei sicuro di voler eliminare questo record?');

    return (
      <>
        <h2 slot="headline">{__('Elimina record')}</h2>
        <p>{text}</p>
        <ul>{this.records.map((record) => <li key={record.getId()}>{this.recordSummary(record, vnode)}</li>)}</ul>
        <md-text-button id="discard-button" slot="footer" dialog-action="cancel">
          {__('No')}
        </md-text-button>
        <md-text-button id="confirm-button" slot="footer" onclick={this.onConfirmButtonClicked.bind(this)}>
          {__('Sì')}
        </md-text-button>
      </>
    );
  }

  recordSummary(record: M, vnode: Vnode<A, this>): Children {
    return __('ID: :recordId', {recordId: record.getId()!});
  }

  async onConfirmButtonClicked() {
    await this.deleteRecord();
  }

  async deleteRecord() {
    try {
      const promises = this.records.map((record) => record.delete());
      await Promise.all(promises);

      // TODO: Better way for pluralization in i18n
      void showSnackbar(this.records.length > 1 ? __('Record eliminati!') : __('Record eliminato!'));
      this.close('deleted');
    } catch (error) {
      void showSnackbar(__('Errore durante l\'eliminazione del record! :error', {error: (error as RequestError<{message: string}>).response.message}), false);
    }
  }
}
