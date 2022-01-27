import {
  type PluralResponse,
  Model as BaseModel
} from 'coloquent';
import {snakeCase} from 'lodash-es';

export interface InstantiableModel<T extends Model = Model> {
  new (): (Model | T) & {[prop: string]: any};
}

export type IModel<T extends Model = Model> = InstanceType<InstantiableModel<T>>;

/**
 * The base model for all models.
 */
export abstract class Model extends BaseModel {
  public static relationships: string[] = [];
  protected jsonApiType: string = '';

  constructor() {
    super();

    // Return a proxy of this object to allow dynamic attributes getters and setters
    // eslint-disable-next-line no-constructor-return
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
   */
  static all(): Promise<PluralResponse<IModel>> {
    // @ts-ignore
    return this.with(this.relationships).get();
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
