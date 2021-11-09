import '@material/mwc-circular-progress';

import {type Button} from '@material/mwc-button';
import {type ClassComponent} from 'mithril';

import Component from './Component.jsx';
import Mdi from './Mdi.jsx';

export default class LoadingButton extends Component implements ClassComponent<Button> {
  view(vnode) {
    return (
      <>
        <mwc-button {...this.attrs.all()}>
        <span slot="icon" style="display: inline;">
            <mwc-circular-progress
              indeterminate
              style={`display: none; vertical-align: bottom; ${this.attrs.has('raised') ? '--mdc-theme-primary: #ffffff;' : ''}'`}/>
          {this.attrs.has('icon') ? <Mdi icon={this.attrs.get('icon')}/> : ''}
        </span>
        </mwc-button>
      </>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);
    $(this.element)
      .find('mwc-circular-progress')
      .attr('density', -7);
  }
}
