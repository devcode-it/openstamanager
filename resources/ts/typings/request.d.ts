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
      // TODO: This is a temporary fix, new model library will be implemented later
      data: {
        errors: JSONAPI.Error[],
        message: string
      }
    };
  }
}
