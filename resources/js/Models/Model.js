import BaseModel from 'javel';
import redaxios from 'redaxios';

export default class Model extends BaseModel {
  baseUrl() {
    return '/api';
  }

  makeRequest({
    method, url, data, query
  }) {
    return redaxios({
      method, url, data, params: query
    });
  }
}
