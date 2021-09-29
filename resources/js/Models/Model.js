import BaseModel from 'javel';
import {snakeCase} from 'lodash';
import redaxios from 'redaxios';

export default class Model extends BaseModel {
  urlPath: string;

  baseUrl() {
    return '/api';
  }

  buildUrl({params}): Array {
    return [this.urlPath ?? snakeCase(this.constructor.name), ...params];
  }

  makeRequest({
    method, url, data, query
  }) {
    return redaxios({
      method, url, data, params: query
    });
  }
}
