import '@material/web/focus/md-focus-ring.js';
import '@material/web/icon/icon.js';
import '@material/web/iconbutton/icon-button.js';

import type {MdIconButton} from '@material/web/iconbutton/icon-button.js';
import {css, html, LitElement} from 'lit';
import {customElement, property, state} from 'lit/decorators.js';
import {live} from 'lit/directives/live.js';

/**
 * Top app bar of the catalog.
 */
@customElement('top-app-bar')
export default class TopAppBar extends LitElement {
  /**
   * Whether the drawer is open.
   */
  @property({type: Boolean, attribute: 'drawer-open'}) private drawerOpen = false;

  render() {
    return html`
      <header>
        <div class="default-content">
          <section class="start">
            <md-icon-button
              toggle
              class="menu-button"
              aria-label-selected="open navigation menu"
              aria-label="close navigation menu"
              aria-expanded=${this.drawerOpen ? 'false' : 'true'}
              title="${this.drawerOpen ? 'Close' : 'Open'} navigation menu"
              .selected=${live(this.drawerOpen)}
              @input=${this.onMenuIconToggle}>
                <slot name="menu-button-icon-selected" slot="selected">
                    <md-icon>menu</md-icon>
                </slot>
                <slot name="menu-button-icon">
                    <md-icon>menu_open</md-icon>
                </slot>
            </md-icon-button>
              <slot name="start" class="start-content"></slot>
          </section>

          <section class="end">
              <slot name="end"></slot>
          </section>
        </div>
        <slot></slot>
      </header>
    `;
  }

  /**
   * Toggles the sidebar's open state.
   */
  private onMenuIconToggle(e: InputEvent) {
    this.drawerOpen = !(e.target as MdIconButton).selected;
    this.dispatchEvent(new CustomEvent('menu-button-toggle', {detail: this.drawerOpen, bubbles: true}));
  }

  static styles = css`
      :host,
      header {
          display: block;
          height: var(--top-app-bar-height, calc(48px + 2 * var(--top-app-bar-spacing, 12px)));
      }

      header {
          position: fixed;
          inset: 0 0 auto 0;
          display: flex;
          align-items: center;
          box-sizing: border-box;
          padding: var(--top-app-bar-elements-spacing-left, 12px) var(--top-app-bar-elements-spacing-right, 16px);
          background-color: var(--md-sys-color-surface-container);
          color: var(--md-sys-color-on-surface);
          z-index: 12;
      }

      .default-content {
          width: 100%;
          display: flex;
          align-items: center;
      }

      md-icon-button:not(:defined) {
          width: 40px;
          height: 40px;
          display: flex;
          visibility: hidden;
      }

      md-icon-button * {
          display: block;
      }

      a {
          color: var(--md-sys-color-primary);
          font-size: max(var(--top-app-bar-title-font-size), 22px);
          text-decoration: none;
          padding-inline: 12px;
          position: relative;
          outline: none;
          vertical-align: middle;
      }
      
      .menu-button {
          margin-right: 16px;
          --_selected-icon-color: inherit;
          --_selected-hover-icon-color: inherit;
          --_selected-focus-icon-color: inherit;
          --_selected-hover-state-layer-color: var(--_hover-state-layer-color);
          --_selected-pressed-icon-color: inherit;
          --_selected-pressed-hover-icon-color: inherit;
          --_selected-pressed-focus-icon-color: inherit;
          --_selected-pressed-state-layer-color: var(--_hover-state-layer-color);
      }
      
      .start {
          display: flex;
          align-items: center;
      }

      .start .start-content * {
          color: var(--md-sys-color-primary);
      }

      .end {
          flex-grow: 1;
          display: flex;
          justify-content: flex-end;
      }

      @media (max-width: 768px) {
          .start .start-content {
              display: none;
          }
      }
  `;
}

declare global {
  interface HTMLElementTagNameMap {
    'top-app-bar': TopAppBar;
  }
}
