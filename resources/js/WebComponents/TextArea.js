import {TextArea as MWCTextArea} from '@material/mwc-textarea';

export default class TextArea extends MWCTextArea {
  get nativeValidationMessage() {
    return this.formElement.validationMessage;
  }

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

window.customElements.define('text-area', TextArea);
