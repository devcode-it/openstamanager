import {TextField as MWCTextField} from '@material/mwc-textfield';
import {waitUntil} from 'async-wait-until';
import {
  html,
  type TemplateResult
} from 'lit';

// noinspection JSCheckFunctionSignatures
export default class TextField extends MWCTextField {
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
