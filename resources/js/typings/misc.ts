import type {CommonAttributes} from 'mithril';
import type {Response} from 'redaxios';

export type ErrorResponse = Response<{errors: Record<string, string>}>;

export type JSXElement<T> = Omit<Partial<T>, 'children' | 'style'>
& CommonAttributes<any, any>
& {
  style?: string | Partial<CSSStyleDeclaration>
};

export declare namespace JSONAPI {

  export interface Trace {
    file: string;
    line: number;
    function: string;
    class: string;
    type: string;
  }

  export interface Meta {
    exception: string;
    file: string;
    line: number;
    trace: Trace[];
  }

  export interface Error {
    detail: string;
    meta: Meta;
    status: string;
    title: string;
  }

  export interface RequestError {
    response: {
      data: {
        errors: JSONAPI.Error[]
      }
    };
  }
}
