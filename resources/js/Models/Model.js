import {Model as BaseModel, PluralResponse} from 'coloquent';
import {snakeCase} from 'lodash';

export default class Model extends BaseModel {
  /**
   * Just an alias to the get() method
   */
  static all(): Promise<PluralResponse<InstanceType<Model>>> {
    return this.get();
  }

  getAttribute(attributeName: string): any {
    return super.getAttribute(attributeName);
  }

  getJsonApiBaseUrl(): string {
    return '/api';
  }

  getJsonApiType(): string {
    return (super.getJsonApiType() ?? snakeCase(this.constructor.name));
  }
}
