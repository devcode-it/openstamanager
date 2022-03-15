import {
  Model as BaseModel,
  PaginationStrategy
} from 'coloquent';

import {ValueOf} from '../typings';


/**
 * The base model for all models.
 */
export abstract class Model<A, R = {}> extends BaseModel {
  /**
   * Model's relationships to be eager loaded when calling the `all` method.
   * @see all
   */
  public static relationships: string[] = [];
  protected static paginationStrategy = PaginationStrategy.PageBased;
  protected static jsonApiBaseUrl = '/api/v1';

  /**
   * Returns all the instances of the model.
   */
  static all() {
    // @ts-ignore
    return this.with<this>(this.relationships)
      .get();
  }

  /**
   * Set multiple attributes on the model.
   * @param attributes
   */
  setAttributes(attributes: Record<keyof A, ValueOf<A>>): void {
    for (const [attribute, value] of Object.entries(attributes)) {
      this.setAttribute(attribute, value);
    }
  }

  getAttribute(attributeName: keyof A | string): ValueOf<A> | any {
    return super.getAttribute(attributeName as string);
  }

  setAttribute(attributeName: keyof A | string, value: ValueOf<A> | any) {
    super.setAttribute(attributeName as string, value);
  }

  getRelation(relationName: keyof R | string): ValueOf<R> | null | any {
    return super.getRelation(relationName as string);
  }

  /**
   * Get model ID.
   */
  getId() {
    return this.getApiId();
  }
}
