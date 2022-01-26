import type {
  Attributes,
  CommonAttributes
} from 'mithril';
import type {Response} from 'redaxios';

export type ErrorResponse = Response<{errors: Record<string, string>}>;

export type JSXElement<T> = Omit<Partial<T>, 'children' | 'style'>
& CommonAttributes<any, any>
& {
  children?: JSX.Element | JSX.Element[] | Attributes | Attributes[],
  style?: string | CSSStyleDeclaration
};

