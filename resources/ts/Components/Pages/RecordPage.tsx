import '@material/web/button/outlined-button.js';
import {router} from '@maicol07/inertia-mithril';

import {mdiChevronLeft} from '@mdi/js';
import {
  Children,
  Vnode,
  VnodeDOM
} from 'mithril';
import {Class} from 'type-fest';

import MdIcon from '~/Components/MdIcon';
import Page, {PageAttributes} from '~/Components/Page';
import Model from '~/Models/Model';

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
    if (recordId) {
      const response = await this.recordType.find(recordId);
      this.record = response.getData() || undefined;
    }

    if (!this.record) {
      // @ts-expect-error — This won't be abstract when implemented
      // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
      this.record = new this.recordType();
    }
    m.redraw();
  }

  contents(vnode: Vnode<A>): Children {
    return this.backButton(vnode);
  }

  backButton(vnode: Vnode<A>): Children {
    return (
      <md-outlined-button onclick={this.onBackButtonClicked.bind(this)}>
        <span>{__('Indietro')}</span>
        <MdIcon icon={mdiChevronLeft} slot="icon"/>
      </md-outlined-button>
    );
  }

  onBackButtonClicked(): void {
    window.history.back();
  }
}
