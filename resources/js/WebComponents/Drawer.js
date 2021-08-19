import {css} from 'lit-element';
import {Drawer as MWCDrawer} from '@material/mwc-drawer';

class Drawer extends MWCDrawer {
  static styles = [MWCDrawer.styles, css`
    :first-child {
      border-right: none;
    }
    
    .mdc-drawer-app-content {
      color: var(--mdc-theme-text-primary-on-background);
      background-color: var(--mdc-theme-background);
    }
  `];
}
global.customElements.define('material-drawer', Drawer);
