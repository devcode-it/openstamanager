import {Select as MWCSelect} from '@material/mwc-select';
import {waitUntil} from 'async-wait-until';
import {
  css,
  html,
  type TemplateResult
} from 'lit';

// noinspection JSCheckFunctionSignatures
export default class Select extends MWCSelect {
  static styles = [MWCSelect.styles, css`
    .mdc-select__anchor {
      width: var(--mdc-select-width, 200px) !important;
      height: var(--mdc-select-height, 56px) !important;
    }
  `];

  get nativeValidationMessage() {
    return this.formElement.validationMessage;
  }

  async connectedCallback() {
    super.connectedCallback();

    // Wait until slots are added to DOM
    await waitUntil(() => this.shadowRoot.querySelectorAll('slot[name=icon]').length > 0);

    const slot = this.shadowRoot.querySelector('slot[name=icon]');
    const slotClass = 'mdc-select__icon';
    const rootClass = 'mdc-select--with-leading-icon';
    // noinspection DuplicatedCode
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

  renderLeadingIcon(): TemplateResult {
    return html`
      <span>
          <slot name="icon"></slot>
        </span>
    `;
  }

  /**
   * Fix mwc-select when handling validation message
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

window.customElements.define('material-select', Select);
