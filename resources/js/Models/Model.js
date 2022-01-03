import {collect} from 'collect.js';
import {
  type PluralResponse,
  Model as BaseModel
} from 'coloquent';
import {
  capitalize,
  snakeCase
} from 'lodash-es';

// noinspection JSPotentiallyInvalidConstructorUsage
/**
 * The base model for all models.
 *
 * @property {number} id
 * @abstract
 */
export default class Model extends BaseModel {
  jsonApiType: string;

  /**
   * Specifies the list of relationships, with their model(s) and getters/setters
   *
   * @type {{[p: string]: {model: typeof Model, get, set}}}
   */
  relationValues: {[string]: {
    model: typeof Model | (typeof Model)[],
    get: {[string]: (models: Model | Model[]) => any},
    set: {[string]: (models: Model | Model[]) => void},
  }} = {}

  /**
   * Specifies the list of attributes that should be obtained from the model
   * @type {{[p: string]: string}}
   * @private
   */
  #relationAttributesGet: {[string]: string} = {};
  /**
   * Specifies the list of attributes that should be set in the model
   * @type {{[p: string]: string}}
   * @private
   */
  #relationAttributesSet: {[string]: string} = {};

  constructor() {
    super();
    const collection = collect(this.relationValues);
    this.#relationAttributesGet = collection.pluck('get').keys().all();
    this.#relationAttributesSet = collection.pluck('set').keys().all();

    // Return a proxy of this object to allow dynamic attributes getters and setters
    // eslint-disable-next-line no-constructor-return
    return new Proxy(this, {
      get(target: this, property, receiver) {
        if (property in target.#relationAttributesGet) {
          return target.relationValue(property);
        }

        const snakeCasedProperty = snakeCase(property);
        if (snakeCasedProperty in target.getAttributes()) {
          return target.getAttribute(snakeCasedProperty);
        }

        return Reflect.get(target, property, receiver);
      },
      set(target: this, property, value, receiver) {
        if (property in target.#relationAttributesSet) {
          return target.relationValue(property, 'set', value);
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

  /**
   * Returns the attribute of the specified relationship.
   */
  // eslint-disable-next-line default-param-last
  relationValue(attribute: string, action: 'get' | 'set' = 'get', value: any): void | any {
    const relation = this[`relationAttributes${capitalize(action)}`][attribute];
    const callback = this.relationValues[relation][action][attribute];

    let istanza = this.getRelation(relation);
    const model = this.relationValues[relation].model
    if (!istanza && !Array.isArray(model)) {
      // eslint-disable-next-line new-cap
      istanza = new model();
    }

    return callback(istanza);
  }
}
