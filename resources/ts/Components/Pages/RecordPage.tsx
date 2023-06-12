import '@material/web/button/outlined-button.js';

import {mdiChevronLeft} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import Page, {PageAttributes} from '@osm/Components/Page';
import Model from '@osm/Models/Model';
import {Builder} from 'coloquent';
import {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import {Class} from 'type-fest';

export interface RecordPageAttributes<M extends Model<any, any>> extends PageAttributes {
  record: M;
}

export default abstract class RecordPage<M extends Model<any, any>, A extends RecordPageAttributes<M> = RecordPageAttributes<M>> extends Page<A> {
  abstract recordType: Class<M> & typeof Model<any, any>;
  record?: M;

  public oninit(vnode: Vnode<A, this>): void {
    super.oninit(vnode);
    m.redraw();
  }

  async onbeforeupdate(vnode: VnodeDOM<A, this>) {
    super.onbeforeupdate(vnode);

    const {id: recordId} = route().params as {id: number | string};
    if (recordId !== this.record?.getId()) {
      await this.loadRecord(recordId);
    }
  }

  async loadRecord(recordId?: number | string) {
    if (recordId && recordId !== 'new' && !this.record) {
      try {
        const response = await this.modelQuery().find(recordId);
        this.record = response.getData() || undefined;
      } catch {
        // Do nothing
      }
    }

    if (!this.record) {
      // @ts-expect-error — This won't be abstract when implemented
      // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
      this.record = new this.recordType();
    }
    m.redraw();
  }

  modelQuery(): Builder<M> {
    return this.recordType.query();
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
