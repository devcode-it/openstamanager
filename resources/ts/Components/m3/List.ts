import {List as MDList} from '@material/web/list/lib/list.js';
import {ARIARole} from '@material/web/types/aria';
import {customElement} from 'lit/decorators.js';

@customElement('md-list')
export default class List extends MDList {
  // @ts-expect-error - Workaround for https://github.com/material-components/material-web/issues/3804
  // eslint-disable-next-line unicorn/no-null
  override role: ARIARole | null = null;

  connectedCallback() {
    super.connectedCallback();
    this.role = 'menu';
  }
}
