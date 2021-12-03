import {TextField as MWCTextField} from '@material/mwc-textfield';
import {waitUntil} from 'async-wait-until';
import {
  css,
  html,
  type TemplateResult,
  unsafeCSS
} from 'lit';

import styles from '../../scss/material/text-field.scss';
import classnames from 'classnames';

// noinspection JSCheckFunctionSignatures
export default class TextField extends MWCTextField {
  static styles = [MWCTextField.styles, css`${unsafeCSS(styles)}`];

  static properties = {
    ...MWCTextField.properties,
    comfortable: {
      type: Boolean
    },
    dense: {
      type: Boolean
    },
    compact: {
      type: Boolean
    }
  }

  async connectedCallback() {
    super.connectedCallback();

    // Wait until slots are added to DOM
    await waitUntil(() => this.shadowRoot.querySelectorAll('slot[name^=icon]').length > 0);

    const slots = this.shadowRoot.querySelectorAll('slot[name^=icon]');
    for (const slot: HTMLSlotElement of slots) {
      const slotClass = `mdc-text-field__icon--${slot.name === 'icon' ? 'leading' : 'trailing'}`;
      const rootClass = `mdc-text-field--with-${slot.name === 'icon' ? 'leading' : 'trailing'}-icon`;
      const slotParent = slot.parentElement;
      const rootElement = this.shadowRoot.firstElementChild;

      // Check if slot has content
      if (slot.assignedNodes().length > 0) {
        slotParent.classList.add(slotClass);
        rootElement.classList.add(rootClass);
      }

      // Listen for changes in slot (added/removed)
      slot.addEventListener('slotchange', () => {
        if (slot.assignedNodes().length > 0) {
          slotParent.classList.add(slotClass);
          rootElement.classList.add(rootClass);
        } else {
          slotParent.classList.remove(slotClass);
          rootElement.classList.remove(rootClass);
        }
      });
    }
  }

  render(): TemplateResult {
    const shouldRenderCharCounter = this.charCounter && this.maxLength !== -1;
    const shouldRenderHelperText = !!this.helper || !!this.validationMessage || shouldRenderCharCounter;

    /** @classMap */
    const classes = {
      'mdc-text-field--disabled': this.disabled,
      'mdc-text-field--no-label': !this.label,
      'mdc-text-field--filled': !this.outlined,
      'mdc-text-field--outlined': this.outlined,
      'mdc-text-field--with-leading-icon': this.icon,
      'mdc-text-field--with-trailing-icon': this.iconTrailing,
      'mdc-text-field--end-aligned': this.endAligned,
      'mdc-text-field--comfortable': this.comfortable,
      'mdc-text-field--dense': this.dense,
      'mdc-text-field--compact': this.compact
    };

    return html`
      <label class="mdc-text-field ${classnames(classes)}">
        ${this.renderRipple()}
        ${this.outlined ? this.renderOutline() : this.renderLabel()}
        ${this.renderLeadingIcon()}
        ${this.renderPrefix()}
        ${this.renderInput(shouldRenderHelperText)}
        ${this.renderSuffix()}
        ${this.renderTrailingIcon()}
        ${this.renderLineRipple()}
      </label>
      ${this.renderHelperText(shouldRenderHelperText, shouldRenderCharCounter)}
    `;
  }

  renderLeadingIcon() {
    return this.renderIcon();
  }

  renderTrailingIcon() {
    return this.renderIcon(true);
  }

  renderIcon(isTrailingIcon: boolean = false): TemplateResult {
    return html`
      <span class="mdc-text-field__icon">
          <slot name="icon${isTrailingIcon ? 'Trailing' : ''}"></slot>
        </span>
    `;
  }

  get nativeValidationMessage() {
    return this.formElement.validationMessage;
  }

  /**
   * Fix mwc-textfield when handling validation message
   * It gets native input validation message when no default validationMessage is set.
   *
   * Related issue:
   * https://github.com/material-components/material-components-web-components/issues/971
   *
   */
  firstUpdated() {
    if (this.validationMessage) {
      this._initialValidationMessage = this.validationMessage;
    }
    super.firstUpdated();
  }

  reportValidity() {
    const isValid = super.reportValidity();
    // Note(cg): override validationMessage only if no initial message set.
    if (!this._initialValidationMessage && !isValid) {
      this.validationMessage = this.nativeValidationMessage;
    }
    return isValid;
  }
}

window.customElements.define('text-field', TextField);
