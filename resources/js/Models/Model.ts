import {
  type PluralResponse,
  Model as BaseModel,
  PaginationStrategy
} from 'coloquent';
import {snakeCase} from 'lodash-es';

import {
  hasGetter,
  hasSetter
} from '../utils';

export interface InstantiableModel<T extends Model = Model> {
  new(): (Model | T) & {[prop: string]: any};
}

export type IModel<T extends Model = Model> = InstanceType<InstantiableModel<T>>;

/**
 * The base model for all models.
 */
export abstract class Model extends BaseModel {
  public static relationships: string[] = [];
  protected static paginationStrategy = PaginationStrategy.PageBased;
  protected static jsonApiBaseUrl = '/api/v1';

  constructor() {
    super();

    // Return a proxy of this object to allow dynamic attributes getters and setters
    // eslint-disable-next-line no-constructor-return
    return new Proxy(this, {
      get(target, property: string, receiver): any {
        const whitelistAttributes = ['attributes', 'relations'];
        // @ts-ignore
        if (hasGetter(target, property) || whitelistAttributes.includes(property) || typeof target[property] === 'function') {
          return Reflect.get(target, property, receiver);
        }

        const snakeCasedProperty = snakeCase(property);
        if (snakeCasedProperty in target.getAttributes()) {
          return target.getAttribute(snakeCasedProperty);
        }

        return Reflect.get(target, property, receiver);
      },
      set(target, property: string, value) {
        if (hasSetter(target, property) || property === 'id') {
          return Reflect.set(target, property, value);
        }

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

  getRelation(relationName: string): IModel | any {
    return super.getRelation(relationName);
  }

  getId() {
    return this.getApiId();
  }
}
