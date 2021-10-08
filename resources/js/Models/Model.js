import {Model as BaseModel, PluralResponse} from 'coloquent';
import {snakeCase} from 'lodash-es';

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

  setAttribute(attributeName: string, value: any): void {
    super.setAttribute(attributeName, value);
  }

  setAttributes(attributes: { [string]: any }): void {
    for (const [attribute, value] of Object.entries(attributes)) {
      this.setAttribute(attribute, value);
    }
  }

  getJsonApiBaseUrl(): string {
    return '/api';
  }

  getJsonApiType(): string {
    return (super.getJsonApiType() ?? snakeCase(this.constructor.name));
  }
}
