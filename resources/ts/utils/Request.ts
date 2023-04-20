import {RequestOptions as MithrilRequestOptions} from 'mithril';
import {Cookies} from 'typescript-cookie';

export type RequestMethods = 'get' | 'head' | 'post' | 'put' | 'delete' | 'connect' | 'options' | 'trace' | 'patch' | string;

export interface RequestOptions<R = any> extends MithrilRequestOptions<R> {
  url?: string,
  renewCSRF?: boolean,
  renewCSRFOnFailure?: boolean,
  method?: RequestMethods
  beforeRequest?: (options: RequestOptionsWithUrl) => Promise<void>
  afterRequest?: (response: Promise<R>, options: RequestOptionsWithUrl) => void,
  xsrfCookieName?: string,
  xsrfHeaderName?: string
}

export interface RequestOptionsWithUrl<R = any> extends RequestOptions<R> {
  url: string;
}

export interface RequestError<T = {message: string}> extends Error {
  code: number;
  response: T;
}

export default class Request<R> {
  options: RequestOptionsWithUrl<R> = {
    url: '',
    headers: {},
    renewCSRF: false,
    renewCSRFOnFailure: false, // Renew CSRF token if request fails with 419 status code (CSRF token expired; risky since it can cause an infinite loop)
    withCredentials: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN'
  };

  constructor(options: RequestOptions) {
    this.options = {...this.options, ...options};
  }

  /**
   * Sends the request
   *
   * @throws {RequestError} If request has an error
   */
  public async send() {
    await this.beforeSendingRequest();
    const response = m.request<R>(this.options);
    this.afterSendingRequest(response);

    return response;
  }

  /**
   * Actions to perform before sending the request
   * @private
   */
  private async beforeSendingRequest() {
    this.phpWorkaround();
    this.xsfrAutoHeader();

    await this.options.beforeRequest?.(this.options);
  }

  /**
   * Workaround for PHP issue with PUT/PATCH/DELETE requests and FormData
   *
   * @see https://bugs.php.net/bug.php?id=55815
   * @private
   */
  private phpWorkaround() {
    if (this.options.method && !['get', 'post'].includes(this.options.method) && this.options.body instanceof FormData) {
      this.options.body.append('_method', this.options.method);
      this.options.method = 'post';
    }
  }

  /**
   * Automatically set the XSRF header if the cookie is set
   *
   * @private
   */
  private xsfrAutoHeader() {
    const token = Cookies.get(this.options.xsrfCookieName) as string | undefined;
    if (token && this.options.xsrfHeaderName) {
      this.options.headers![this.options.xsrfHeaderName] = decodeURIComponent(token);
    }
  }

  /**
   * Actions to perform after sending the request
   *
   * @private
   */
  private afterSendingRequest(response: Promise<R>) {
    this.options.afterRequest?.(response, this.options);
  }

  static get<R>(url: string, parameters?: RequestOptions['params'], options?: RequestOptions) {
    return this.sendRequest<R>('get', url, parameters, options);
  }

  static post<R>(url: string, data?: RequestOptions['body'], options?: RequestOptions) {
    return this.sendRequest<R>('post', url, data, options);
  }

  static put<R>(url: string, data?: RequestOptions['body'], options?: RequestOptions) {
    return this.sendRequest<R>('put', url, data, options);
  }

  static patch<R>(url: string, data?: RequestOptions['body'], options?: RequestOptions) {
    return this.sendRequest<R>('patch', url, data, options);
  }

  static delete<R>(url: string, options?: RequestOptions) {
    return this.sendRequest<R>('delete', url, undefined, options);
  }

  /**
   * Sends a request
   * @param method The method of the request (get, post, put, patch, delete)
   * @param url The URL of the request
   * @param data The data to send
   * @param options The options of the request
   * @private
   */
  static sendRequest<R>(
    method: RequestMethods,
    url: string,
    data?: RequestOptions['params'] | RequestOptions['body'],
    options: RequestOptions = {}
  ) {
    if (method === 'get') {
      options.params = data as RequestOptions['params'];
    } else {
      options.body = data;
    }
    options.url ??= url;

    return (new Request<R>({method, ...options})).send();
  }
}
