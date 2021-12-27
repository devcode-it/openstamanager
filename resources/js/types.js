import {ListItemBase} from '@material/mwc-list/mwc-list-item-base';
import type {
  TextAreaCharCounter,
  TextFieldInputMode,
  TextFieldType
} from '@material/mwc-textfield';

export type FieldT = {
  id?: string,
  name?: string,
  value?: string,
  label?: string,
  outlined?: boolean,
  helper?: string,
  icon?: string,
  placeholder?: string,
  disabled?: boolean,
  required?: boolean,
  validity?: ValidityState,
  validityTransform?: (value: string, nativeValidity: ValidityState) => Partial<ValidityState> |
    null,
  validateOnInitialRender?: boolean,
  validationMessage?: string
}

export type TextFieldT = FieldT | {
  type?: TextFieldType,
  prefix?: string,
  suffix?: string,
  iconTrailing?: string,
  charCounter?: boolean,
  helperPersistent?: boolean | string,
  minLength?: number,
  maxLength?: number,
  pattern?: string,
  min?: number | string,
  max?: number | string,
  size?: number | null,
  step?: number | null,
  autoValidate?: boolean,
  willValidate?: boolean,
  name?: string,
  inputMode?: TextFieldInputMode,
  readOnly?: boolean,
  autocapitalize: 'on' | 'off' | 'sentences' | 'none' | 'words' | 'characters',
  endAligned?: boolean,
  elementType: 'text-field',
  ...
};

export type TextAreaT = FieldT | {
  rows?: number,
  cols?: number,
  type?: TextFieldType,
  iconTrailing?: string,
  charCounter?: boolean | TextAreaCharCounter,
  willValidate?: boolean,
  helperPersistent?: boolean | string,
  maxLength?: number,
  elementType: 'text-area',
  ...
};

export type SelectT = FieldT | {
  multiple?: boolean,
  naturalMenuWidth?: boolean,
  fixedMenuPosition?: boolean,willValidate?: boolean,
  elementType: 'material-select',
  selected?: ListItemBase | null,
  items?: ListItemBase[],
  index?: number,
  ...
};
