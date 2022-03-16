import {RequestOptions as MithrilRequestOptions} from 'mithril';

export type RequestMethods = 'get' | 'post' | 'put' | 'patch' | 'delete' | string;

export interface RequestOptions<T = any> extends MithrilRequestOptions<T> {
  url?: string,
  renewCSRF?: boolean,
  method?: RequestMethods
  beforeRequest?: (options: RequestOptionsWithUrl) => Promise<void> | Promise<RequestOptionsWithUrl>
  afterRequest?: (options: RequestOptionsWithUrl) => void | RequestOptionsWithUrl
}

export interface RequestOptionsWithUrl extends RequestOptions {
  url: string;
}

export class Request {
  options: RequestOptionsWithUrl = {
    url: '',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') as string
    },
    renewCSRF: true
  };

  constructor(options: RequestOptions) {
    this.options = {...this.options, ...options};
  }

  public async send() {
    await this.beforeSendingRequest();
    const request = m.request(this.options);
    this.afterSendingRequest();
    return request;
  }

  private async beforeSendingRequest() {
    if (this.options.beforeRequest) {
      const newOptions = await this.options.beforeRequest(this.options);
      if (newOptions) {
        this.options = newOptions;
      }
    }
  }

  private afterSendingRequest() {
    if (this.options.method !== 'get' && this.options.renewCSRF) {
      void this.renewCSRFToken();
    }

    if (this.options.afterRequest) {
      const newOptions = this.options.afterRequest(this.options);
      if (newOptions) {
        this.options = newOptions;
      }
    }
  }

  async renewCSRFToken() {
    const response = await Request.get(route('csrf.renew'), undefined, {
      background: true,
      renewCSRF: false
    }) as string;
    document.querySelector('meta[name="csrf-token"]')
      ?.setAttribute('content', response);
  }

  static get(url: string, parameters?: RequestOptions['params'], options?: RequestOptions) {
    return this.sendRequest('get', url, parameters, options);
  }

  static post(url: string, data?: RequestOptions['body'], options?: RequestOptions) {
    return this.sendRequest('post', url, data, options);
  }

  static put(url: string, data?: RequestOptions['body'], options?: RequestOptions) {
    return this.sendRequest('put', url, data, options);
  }

  static patch(url: string, data?: RequestOptions['body'], options?: RequestOptions) {
    return this.sendRequest('patch', url, data, options);
  }

  static delete(url: string, options?: RequestOptions) {
    return this.sendRequest('delete', url, undefined, options);
  }

  private static sendRequest(method: RequestMethods, url: string, data?: RequestOptions['params'] | RequestOptions['body'], options: RequestOptions = {}) {
    if (method === 'get') {
      options.params = data as RequestOptions['params'];
    } else {
      options.body = data;
    }
    options.url ??= url;

    return (new Request({method, ...options})).send();
  }
}
