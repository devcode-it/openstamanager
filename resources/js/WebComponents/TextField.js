import {TextField as MWCTextField} from '@material/mwc-textfield';
import {waitUntil} from 'async-wait-until';
import classnames from 'classnames';
import {
  html,
  type TemplateResult
} from 'lit';

// noinspection JSCheckFunctionSignatures
export default class TextField extends MWCTextField {
  async connectedCallback() {
    super.connectedCallback();
    await waitUntil(() => this.shadowRoot.querySelectorAll('slot[name^=icon]').length > 0);
    const slots = this.shadowRoot.querySelectorAll('slot[name^=icon]');
    for (const slot: HTMLSlotElement of slots) {
      if (slot.assignedNodes().length > 0) {
        this[slot.name] = ' ';
      }
    }
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

  renderLeadingIcon() {
    return this.renderIcon();
  }

  renderTrailingIcon() {
    return this.renderIcon(true);
  }

  renderIcon(isTrailingIcon: boolean = false): TemplateResult {
    const classes = {
      'mdc-text-field__icon--leading': !isTrailingIcon,
      'mdc-text-field__icon--trailing': isTrailingIcon
    };

    return html`
      <span class="mdc-text-field__icon ${classnames(classes)}">
          <slot name="icon${isTrailingIcon ? 'Trailing' : ''}"></slot>
        </span>`;
  }
}

window.customElements.define('text-field', TextField);
