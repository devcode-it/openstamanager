import {RequestHttpClientResponse} from '~/Models/Http/RequestHttpClientResponse';
import type {
  HttpClientPromise,
  HttpClientResponse
} from 'coloquent';
import type {Thenable} from 'coloquent/dist/httpclient/Types';

export default class RequestHttpClientPromise implements HttpClientPromise {
  constructor(private response: Promise<any>) {
  }

  catch<U>(onRejected?: (error: any) => (Thenable<U> | U)): Promise<U> {
    return this.response.catch(onRejected) as Promise<U>;
  }

  // eslint-disable-next-line unicorn/no-thenable
  then<U>(
    onFulfilled?: (value: HttpClientResponse) => (Thenable<U> | U),
    onRejected?: (error: any) => void | (Thenable<U> | U)
  ): Promise<U> {
    const wrappedOnFulfilled = onFulfilled !== undefined
      ? ((responsePromise: any) => onFulfilled(new RequestHttpClientResponse(responsePromise)))
      : undefined;
    return this.response.then<U>(
      wrappedOnFulfilled,
      // @ts-ignore
      onRejected
    );
  }
}
