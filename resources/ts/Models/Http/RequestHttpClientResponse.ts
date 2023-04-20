import type {HttpClientResponse} from 'coloquent';

export class RequestHttpClientResponse implements HttpClientResponse {
  constructor(private response: any) {
  }

  getData(): any {
    return this.response;
  }

  getUnderlying(): any {
    return this.response;
  }
}
