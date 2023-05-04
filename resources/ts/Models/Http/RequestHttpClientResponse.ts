import type {HttpClientResponse} from 'coloquent';

export default class RequestHttpClientResponse implements HttpClientResponse {
  constructor(private response: any) {}

  getData(): any {
    return this.response;
  }

  getUnderlying(): any {
    return this.response;
  }
}
