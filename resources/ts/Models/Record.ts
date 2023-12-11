import {Attr, Model, SpraypaintBase} from 'spraypaint';
import {Cookies} from 'typescript-cookie';

@Model()
export default class Record extends SpraypaintBase {
  static baseUrl = '';
  static apiNamespace = '/api/restify';
  static clientApplication = 'OpenSTAManager';

  @Attr({persist: false}) createdAt!: string;
  @Attr({persist: false}) updatedAt!: string;

  isNew(): boolean {
    return this.id === undefined;
  }

  static fetchOptions(): RequestInit {
    // Get the CSRF token from the meta tag or the cookie
    // https://laravel.com/docs/10.x/csrf#csrf-x-csrf-token
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || Cookies.get('XSRF-TOKEN') as string;

    return {
      headers: {
        'X-XSRF-TOKEN': token,
        Accept: 'application/vnd.api+json',
        'Content-Type': 'application/vnd.api+json'
      }
    };
  }
}
