import {styles} from '@material/web/navigationdrawer/lib/navigation-drawer-styles.css.js';
import {NavigationDrawer as MDNavigationDrawer} from '@material/web/navigationdrawer/lib/navigation-drawer.js';
import {styles as sharedStyles} from '@material/web/navigationdrawer/lib/shared-styles.css.js';
import {css} from 'lit';
import {customElement} from 'lit/decorators.js';

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
