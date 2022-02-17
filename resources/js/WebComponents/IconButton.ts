/* eslint-disable @typescript-eslint/unbound-method,sonarjs/no-nested-template-literals */
import {IconButton as MWCIconButton} from '@material/mwc-icon-button';
import styles from '@openstamanager/scss/material/icon-button.scss';
import classnames from 'classnames';
import {
  css,
  html,
  TemplateResult,
  unsafeCSS
} from 'lit';
import {
  customElement,
  property
} from 'lit/decorators.js';
import {ifDefined} from 'lit/directives/if-defined.js';

// noinspection HtmlUnknownAttribute
@customElement('icon-button')
export default class IconButton extends MWCIconButton {
  static styles = [...MWCIconButton.styles, css`${unsafeCSS(styles)}`];

  @property({type: Boolean}) declare tight: boolean;
  @property({type: Boolean}) declare comfortable: boolean;
  @property({type: Boolean}) declare dense: boolean;
  @property({type: Boolean}) declare compact: boolean;
  @property({type: Boolean, attribute: 'extra-compact'}) declare extraCompact: boolean;

  /** @soyTemplate */
  protected override render(): TemplateResult {
    /** @classMap */
    const classes = {
      'mdc-icon-button--tight': this.tight,
      'mdc-icon-button--comfortable': this.comfortable,
      'mdc-icon-button--dense': this.dense,
      'mdc-icon-button--compact': this.compact,
      'mdc-icon-button--extra-compact': this.extraCompact
    };

    return html`
      <button
        class="mdc-icon-button mdc-icon-button--display-flex ${classnames(classes)}"
        aria-label="${this.ariaLabel || this.icon}"
        aria-haspopup="${ifDefined(this.ariaHasPopup)}"
        ?disabled="${this.disabled}"
        @focus="${this.handleRippleFocus}"
        @blur="${this.handleRippleBlur}"
        @mousedown="${this.handleRippleMouseDown}"
        @mouseenter="${this.handleRippleMouseEnter}"
        @mouseleave="${this.handleRippleMouseLeave}"
        @touchstart="${this.handleRippleTouchStart}"
        @touchend="${this.handleRippleDeactivate}"
        @touchcancel="${this.handleRippleDeactivate}"
      >${this.renderRipple()}
        ${this.icon ? html`<i class="material-icons">${this.icon}</i>` : ''}
        <span
        ><slot></slot
        ></span>
      </button>
    `;
  }
}
