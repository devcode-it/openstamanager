import '@material/mwc-circular-progress';
import '@material/mwc-button';

import type {Button} from '@material/mwc-button';
import type CSS from 'csstype';
import type {VnodeDOM} from 'mithril';

import Component from './Component';
import Mdi from './Mdi';

type Attributes = Partial<Button> & {
  icon?: string
};

declare global {
  namespace JSX {
    interface IntrinsicElements {
      LoadingButton: LoadingButton
    }
  }
}

class LoadingButton extends Component<Attributes> {
  view() {
    return ( // @ts-ignore
      <mwc-button {...this.attrs.all()}>
        <span slot="icon" style="display: inline;">
          <mwc-circular-progress
            indeterminate
            // @ts-ignore
            style={this.getCSSProperties()}
          />
          {this.attrs.has('icon') ? (
            <Mdi icon={this.attrs.get('icon')} />
          ) : (
            ''
          )}
        </span>
      </mwc-button>
    );
  }

  getCSSProperties() {
    const css: CSS.Properties & Record<string, string> = {
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

  oncreate(vnode: VnodeDOM<Attributes>) {
    super.oncreate(vnode);
    $(this.element).find('mwc-circular-progress').attr('density', String(-7));
  }
}

export default LoadingButton;
