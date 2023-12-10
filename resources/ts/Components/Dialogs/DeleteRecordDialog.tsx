import {mdiDelete} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import Model from '@osm/Models/Record';
import {VnodeCollection} from '@osm/typings/jsx';
import {showSnackbar} from '@osm/utils/misc';
import collect from 'collect.js';
import {
  Children,
  Vnode
} from 'mithril';
import {RequestError} from 'mithril-utilities';

import RecordDialog, {RecordDialogAttributes} from './RecordDialog';

export interface DeleteRecordDialogAttributes<M extends Model> extends RecordDialogAttributes<M> {
  records: M | M[];
}

export default class DeleteRecordDialog<M extends Model, A extends DeleteRecordDialogAttributes<M> = DeleteRecordDialogAttributes<M>> extends RecordDialog<M, A> {
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
        <p>{text}</p>
        <ul>{this.records.map((record) => <li key={record.id}>{this.recordSummary(record, vnode)}</li>)}</ul>
      </>
    );
  }

  headline(): Children {
    return <span>{__('Elimina record')}</span>;
  }

  icon(): Children {
    return <MdIcon icon={mdiDelete}/>;
  }

  actions(vnode: Vnode<A, this>): VnodeCollection {
    return collect({
      cancel: (
        <md-text-button id="discard-button" onclick={this.onCancelButtonClicked.bind(this)}>
          {__('No')}
        </md-text-button>
      ),
      confirm: (
        <md-text-button id="confirm-button" onclick={this.onConfirmButtonClicked.bind(this)}>
          {__('Sì')}
        </md-text-button>
      )
    });
  }

  recordSummary(record: M, vnode: Vnode<A, this>): Children {
    return __('ID: :recordId', {recordId: record.id!});
  }

  async onConfirmButtonClicked() {
    await this.deleteRecord();
  }

  onCancelButtonClicked() {
    void this.close('cancel');
  }

  async deleteRecord() {
    try {
      const promises = this.records.map((record) => record.destroy());
      await Promise.all(promises);

      // TODO: Better way for pluralization in i18n
      void showSnackbar(this.records.length > 1 ? __('Record eliminati!') : __('Record eliminato!'));
      void this.close('deleted');
    } catch (error) {
      void showSnackbar(__('Errore durante l\'eliminazione del record! :error', {error: (error as RequestError<{message: string}>).response.message}), false);
    }
  }
}
