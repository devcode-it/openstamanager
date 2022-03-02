import {IconButton as MWCIconButton} from '@material/mwc-icon-button';
import styles from '@openstamanager/scss/material/icon-button.scss';
import classnames from 'classnames';
import {
  css,
  html,
  type TemplateResult,
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

  protected override render(): TemplateResult {
    /** @classMap */
    const classes = {
      'mdc-icon-button--tight': this.tight,
      'mdc-icon-button--comfortable': this.comfortable,
      'mdc-icon-button--dense': this.dense,
      'mdc-icon-button--compact': this.compact,
      'mdc-icon-button--extra-compact': this.extraCompact
    };

    const icon = this.icon ? html`<i class="material-icons">${this.icon}</i>` : '';

    return html`
      <button
        class="mdc-icon-button mdc-icon-button--display-flex ${classnames(classes)}"
        aria-label="${this.ariaLabel || this.icon}"
        aria-haspopup="${ifDefined(this.ariaHasPopup)}"
        ?disabled="${this.disabled}"
        @focus="${this.handleRippleFocus.bind(this)}"
        @blur="${this.handleRippleBlur.bind(this)}"
        @mousedown="${this.handleRippleMouseDown.bind(this)}"
        @mouseenter="${this.handleRippleMouseEnter.bind(this)}"
        @mouseleave="${this.handleRippleMouseLeave.bind(this)}"
        @touchstart="${this.handleRippleTouchStart.bind(this)}"
        @touchend="${this.handleRippleDeactivate.bind(this)}"
        @touchcancel="${this.handleRippleDeactivate.bind(this)}"
      >
        ${this.renderRipple()}
        ${icon}
        <span>
          <slot></slot>
        </span>
      </button>
    `;
  }
}
