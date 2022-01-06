import {TopAppBar as MWCTopAppBar} from '@material/mwc-top-app-bar';
import {css} from 'lit';
// eslint-disable-next-line import/extensions
import {customElement} from 'lit/decorators.js';

declare global {
  namespace JSX {
    interface IntrinsicElements {
      'top-app-bar': Partial<TopAppBar>;
    }
  }
}

@customElement('top-app-bar')
export default class TopAppBar extends MWCTopAppBar {
  static styles = [...MWCTopAppBar.styles, css`
    header.mdc-top-app-bar {
      border-bottom: 1px solid var(--mdc-theme-outline-color, #e0e0e0);
      z-index: 7;
    }
  `];
}
