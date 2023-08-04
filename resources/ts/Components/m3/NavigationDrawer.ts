import {NavigationDrawer as MDNavigationDrawer} from '@material/web/labs/navigationdrawer/internal/navigation-drawer.js';
import {styles} from '@material/web/labs/navigationdrawer/internal/navigation-drawer-styles.css.js';
import {styles as sharedStyles} from '@material/web/labs/navigationdrawer/internal/shared-styles.css.js';
import {css} from 'lit';
import {customElement} from 'lit/decorators.js';

declare global {
  interface HTMLElementTagNameMap {
    'md-navigation-drawer': MdNavigationDrawer;
  }
}

@customElement('md-navigation-drawer')
export default class MdNavigationDrawer extends MDNavigationDrawer {
  static override readonly styles = [sharedStyles, styles, css`
    :host {
      display: inline-flex;
    }

    .md3-navigation-drawer--opened {
      width: var(--_container-width);
      height: var(--_container-height);
    }

    .md3-navigation-drawer__slot-content {
      width: inherit;
    }
  `];
}
