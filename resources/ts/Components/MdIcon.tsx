import '@material/web/icon/icon.js';

import type MaterialIcons from '@mdi/js';

import {Component} from './Component';

export interface Attributes extends Partial<SVGElement> {
  icon: typeof MaterialIcons | string;
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
