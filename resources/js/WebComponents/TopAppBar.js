import {css} from 'lit-element';
import {TopAppBar as MWCTopAppBar} from '@material/mwc-top-app-bar';

class TopAppBar extends MWCTopAppBar {
  static styles = [MWCTopAppBar.styles, css`
    header.mdc-top-app-bar {
      border-bottom: 1px solid var(--mdc-theme-outline-color, #e0e0e0);
      z-index: 7;
    }
  `];
}
global.customElements.define('top-app-bar', TopAppBar);
