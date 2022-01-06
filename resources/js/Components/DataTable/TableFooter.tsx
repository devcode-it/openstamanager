import type {Vnode} from 'mithril';

import Component from '../Component';

export default class TableFooter extends Component<{}> {
  view(vnode: Vnode) {
    return <tfoot {...this.attrs.all()}>{vnode.children}</tfoot>;
  }
}
