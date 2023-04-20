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
      errors: JSONAPI.Error[]
    };
  }
}
