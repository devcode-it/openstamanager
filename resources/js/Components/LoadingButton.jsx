import '@material/mwc-circular-progress';

import {type Button} from '@material/mwc-button';
import {type ClassComponent} from 'mithril';

import Component from './Component.jsx';
import Mdi from './Mdi.jsx';
import type CSS from 'csstype';

export default class LoadingButton extends Component implements ClassComponent<Button> {
  view(vnode) {
    return (
      <>
        <mwc-button {...this.attrs.all()}>
        <span slot="icon" style="display: inline;">
            <mwc-circular-progress
              indeterminate
              style={this.getCSSProperties()}/>
          {this.attrs.has('icon') ? <Mdi icon={this.attrs.get('icon')}/> : ''}
        </span>
        </mwc-button>
      </>
    );
  }

  getCSSProperties() {
    const css: CSS.Properties<> = {
      display: 'none',
      verticalAlign: 'bottom'
    };

    if (this.attrs.has('raised')) {
      css['--mdc-theme-primary'] = '#ffffff';
    }

    if (this.attrs.has('icon')) {
      css.marginRight = '8px';
    }

    return css;
  }

  oncreate(vnode) {
    super.oncreate(vnode);
    $(this.element)
      .find('mwc-circular-progress')
      .attr('density', -7);
  }
}
