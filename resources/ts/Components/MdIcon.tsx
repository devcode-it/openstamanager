import '@material/web/icon/icon.js';

import type MaterialIcons from '@mdi/js';
import {Component} from 'mithril-utilities';

export interface Attributes extends Omit<Partial<SVGElement>, 'className'> {
  icon: typeof MaterialIcons | string;
  className?: string;
}

export default class MdIcon extends Component<Attributes> {
  view() {
    const icon = this.attrs.pull('icon');
    const viewBox = this.attrs.pull('viewBox');
    return (
      <md-icon className="mdi" {...this.attrs.all()}>
        <svg viewBox={viewBox ?? '0 0 24 24'}>
          <path d={icon}/>
        </svg>
      </md-icon>
    );
  }
}
