import {styles as filledForcedColorsStyles} from '@material/web/textfield/lib/filled-forced-colors-styles.css.js';
import {styles as filledStyles} from '@material/web/textfield/lib/filled-styles.css.js';
import {FilledTextField as MdFilledTextField} from '@material/web/textfield/lib/filled-text-field.js';
import {styles as sharedStyles} from '@material/web/textfield/lib/shared-styles.css.js';
import {PropertyValues} from 'lit';
import {customElement} from 'lit/decorators.js';
import {literal} from 'lit/static-html.js';


@customElement('md-filled-text-field')
export default class FilledTextField extends MdFilledTextField {
  static override styles = [sharedStyles, filledStyles, filledForcedColorsStyles];
  static formAssociated = true;
  internals: ElementInternals;

  protected override readonly fieldTag = literal`md-filled-field`;

  constructor() {
    super();
    this.internals = this.attachInternals();
  }

  protected updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('value')) {
      this.internals.setFormValue(this.value);
    }
  }
}
