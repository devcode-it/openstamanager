import '@material/web/button/outlined-button.js';

import {mdiChevronLeft} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import Page, {PageAttributes} from '@osm/Components/Page';
import Model from '@osm/Models/Record';
import {showSnackbar} from '@osm/utils/misc';
import {
  Children,
  Vnode
} from 'mithril';
import {Scope} from 'spraypaint';
import {Class} from 'type-fest';

export interface RecordPageAttributes<M extends Model> extends PageAttributes {
  record: M;
}

export default abstract class RecordPage<M extends Model, A extends RecordPageAttributes<M> = RecordPageAttributes<M>> extends Page<A> {
  abstract recordType: Class<M> & typeof Model;
  record?: M;

  async oninit(vnode: Vnode<A, this>) {
    super.oninit(vnode);
    const {id: recordId} = route().params as {id: number | string};
    if (recordId !== this.record?.id) {
      await this.loadRecord(recordId);
    }
  }

  async loadRecord(recordId?: number | string) {
    if (recordId && recordId !== 'new' && !this.record) {
      try {
        const response = await this.modelQuery().find(recordId);
        this.record = response.data || undefined;
      } catch (error) {
        // eslint-disable-next-line no-console
        console.error(error);
        void showSnackbar(__('Errore durante il caricamento del record'));
        // Do nothing
      }
    }

    if (!this.record) {
      this.record = new this.recordType();
    }
    m.redraw();
  }

  modelQuery(): Scope<M> {
    return this.recordType as unknown as Scope<M>;
  }

  contents(vnode: Vnode<A>): Children {
    return this.backButton(vnode);
  }

  backButton(vnode: Vnode<A>): Children {
    return (
      <md-outlined-button onclick={this.onBackButtonClicked.bind(this)} style={{width: 'max-content'}}>
        <span>{__('Indietro')}</span>
        <MdIcon icon={mdiChevronLeft} slot="icon"/>
      </md-outlined-button>
    );
  }

  onBackButtonClicked(): void {
    window.history.back();
  }
}
