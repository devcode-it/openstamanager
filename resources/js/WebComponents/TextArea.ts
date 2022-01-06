import {TextArea as MWCTextArea} from '@material/mwc-textarea';
// eslint-disable-next-line import/extensions
import {customElement} from 'lit/decorators.js';

import {type JSXElement} from '../types';

declare global {
  namespace JSX {
    interface IntrinsicElements {
      'text-area': JSXElement<TextArea>;
    }
  }
}

@customElement('text-area')
export default class TextArea extends MWCTextArea {
  private _initialValidationMessage: string | undefined;

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
