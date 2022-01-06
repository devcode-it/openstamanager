import {Drawer as MWCDrawer} from '@material/mwc-drawer';
import {css} from 'lit';
// eslint-disable-next-line import/extensions
import {customElement} from 'lit/decorators.js';

import type {JSXElement} from '../types';

declare global {
  namespace JSX {
    interface IntrinsicElements {
      'material-drawer': JSXElement<MaterialDrawer>;
    }
  }
}

@customElement('material-drawer')
export default class MaterialDrawer extends MWCDrawer {
  static styles = [
    ...MWCDrawer.styles,
    css`
    :first-child {
      border-right: none;
    }
    
    .mdc-drawer-app-content {
      color: var(--mdc-theme-text-primary-on-background);
      background-color: var(--mdc-theme-background);
    }
    
    .mdc-drawer {
      height: calc(100% - 64px);
    }
  `];
}
