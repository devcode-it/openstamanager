import {styles} from '@material/web/navigationdrawer/lib/navigation-drawer-modal-styles.css.js';
import {NavigationDrawerModal as MDNavigationDrawerModal} from '@material/web/navigationdrawer/lib/navigation-drawer-modal.js';
import {styles as sharedStyles} from '@material/web/navigationdrawer/lib/shared-styles.css.js';
import {css} from 'lit';
import {customElement} from 'lit/decorators.js';

@customElement('md-navigation-drawer-modal')
export default class MdNavigationDrawer extends MDNavigationDrawerModal {
  static override readonly styles = [sharedStyles, styles, css`
    :host {
      --md-navigation-drawer-modal-container-color: var(--md-sys-color-surface);
      z-index: 10;
    }
    /*.md3-navigation-drawer-modal .md3-navigation-drawer-modal__slot-content {
      padding-top: 64px;
    }*/
  `];
}
