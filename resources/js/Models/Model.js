import BaseModel from 'javel';
import {snakeCase} from 'lodash';
import redaxios from 'redaxios';

// noinspection JSUnusedGlobalSymbols
export default class Model extends BaseModel {
  urlPath: string;

  baseUrl(): string {
    return '/api';
  }

  buildUrl({params}): Array {
    return [this.urlPath ?? snakeCase(this.constructor.name), ...params];
  }

  makeRequest({
    method, url, data, query
  }: {
    method: 'get' | 'post' | 'put' | 'patch' | 'delete' | 'options' | 'head' | 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' | 'OPTIONS' | 'HEAD',
    url: string,
    data: any,
    query: {...}
  }): Promise {
    return redaxios({
      method, url, data, params: query
    });
  }
}
