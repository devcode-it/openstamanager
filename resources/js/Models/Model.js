import {
  Model as BaseModel,
  PluralResponse
} from 'coloquent';
import {
  capitalize,
  snakeCase
} from 'lodash-es';

/**
 * The base model for all models.
 *
 * @property {number} id
 * @abstract
 */
export default class Model extends BaseModel {
  jsonApiType: string;

  constructor() {
    super();

    // Return a proxy of this object to allow dynamic attributes getters and setters
    return new Proxy(this, {
      get(target: this, property, receiver) {
        const accessor = target[`get${capitalize(property)}Attribute`];
        if (typeof accessor === 'function') {
          return accessor();
        }

        const snakeCasedProperty = snakeCase(property);
        if (snakeCasedProperty in target.getAttributes()) {
          return target.getAttribute(snakeCasedProperty);
        }

        return Reflect.get(target, property, receiver);
      },
      set(target: this, property, value, receiver) {
        const mutator = target[`set${capitalize(property)}Attribute`];
        if (typeof mutator === 'function') {
          return mutator(value);
        }

        const snakeCasedProperty = snakeCase(property);
        if (snakeCasedProperty in target.getAttributes()) {
          target.setAttribute(snakeCasedProperty, value);
          return true;
        }

        return Reflect.set(target, property, value, receiver);
      }
    });
  }

  /**
   * Just an alias to the get() method.
   *
   * Returns all the instances of the model.
   */
  static all(): Promise<PluralResponse<InstanceType<Model>>> {
    return this.get();
  }

  setAttributes(attributes: { [string]: any }): void {
    for (const [attribute, value] of Object.entries(attributes)) {
      this[attribute] = value;
    }
  }

  getAttribute(attributeName: string): any {
    return super.getAttribute(attributeName);
  }

  setAttribute(attributeName: string, value: any) {
    super.setAttribute(attributeName, value);
  }

  getAttributes(): { [p: string]: any } {
    return super.getAttributes();
  }

  getJsonApiBaseUrl(): string {
    return '/api';
  }

  getJsonApiType(): string {
    return (super.getJsonApiType() ?? snakeCase(this.constructor.name));
  }
}
