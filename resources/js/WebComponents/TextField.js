import {TextField as MWCTextField} from '@material/mwc-textfield';

export default class TextField extends MWCTextField {
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
