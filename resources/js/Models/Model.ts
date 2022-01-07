import {
  type PluralResponse,
  Model as BaseModel
} from 'coloquent';
import {snakeCase} from 'lodash';

export interface InstantiableModel<T extends Model> {
  new (): Model | T;
}

export type IModel<T extends Model = Model> = InstanceType<InstantiableModel<T>>;

/**
 * The base model for all models.
 */
export abstract class Model extends BaseModel {
  jsonApiType: string = '';
  [prop: string]: any;

  constructor() {
    super();

    // Return a proxy of this object to allow dynamic attributes getters and setters
    // eslint-disable-next-line no-constructor-return, @typescript-eslint/no-unsafe-return
    return new Proxy(this, {
      get(target, property: string, receiver): any {
        const snakeCasedProperty = snakeCase(property);
        if (snakeCasedProperty in target.getAttributes()) {
          return target.getAttribute(snakeCasedProperty);
        }

        return Reflect.get(target, property, receiver);
      },
      set(target, property: string, value) {
        target.setAttribute(snakeCase(property), value);
        return true;
      }
    });
  }

  /**
   * Just an alias to the get() method.
   *
   * Returns all the instances of the model.
   */ // @ts-ignore
  static all(): Promise<PluralResponse<InstanceType<Model>>> {
    // @ts-ignore
    return this.get();
  }

  setAttributes(attributes: Record<string, any>): void {
    for (const [attribute, value] of Object.entries(attributes)) {
      this.setAttribute(attribute, value);
    }
  }

  getAttribute(attributeName: string): any {
    return super.getAttribute(attributeName);
  }

  setAttribute(attributeName: string, value: any) {
    super.setAttribute(attributeName, value);
  }

  getAttributes(): {[p: string]: any} {
    return super.getAttributes();
  }

  getJsonApiBaseUrl(): string {
    return '/api';
  }

  getJsonApiType(): string {
    return (super.getJsonApiType() ?? snakeCase(this.constructor.name));
  }

  getId() {
    return this.getApiId();
  }
}
