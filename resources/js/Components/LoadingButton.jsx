import '@material/mwc-circular-progress';

import {type Button} from '@material/mwc-button';
import type CSS from 'csstype';
import {type ClassComponent} from 'mithril';
import PropTypes from 'prop-types';

import Component from './Component.jsx';
import Mdi from './Mdi.jsx';

class LoadingButton extends Component implements ClassComponent<{ ...Button, icon?: string }> {
  static propTypes = {
    icon: PropTypes.string,
    raised: PropTypes.bool,
    outlined: PropTypes.bool
  };

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

export default LoadingButton;
