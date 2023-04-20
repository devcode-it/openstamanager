import classnames, {Argument as ClassNames} from 'classnames';
import collect, {Collection} from 'collect.js';
import Mithril from 'mithril';
import type {
  Children,
  ClassComponent,
  Vnode,
  VnodeDOM
} from 'mithril';

export interface Attributes {
}

interface AttributesCollection<T extends Attributes> extends Collection<T> {
  addClassNames(...classNames: ClassNames[]): void;
}

// noinspection SpellCheckingInspection,JSUnusedGlobalSymbols,JSUnusedLocalSymbols

/**
 * @abstract
 *
 * The `Component` class defines a user interface 'building block'. A component
 * generates a virtual DOM to be rendered on each redraw.
 *
 * Essentially, this is a wrapper for Mithril's components that adds several useful features:
 *
 *  — In the `oninit` and `onbeforeupdate` lifecycle hooks, we store vnode attrs in `this.attrs.
 *    This allows us to use attrs across components without having to pass the vnode to every single
 *    method.
 *  — The static `initAttrs` method allows a convenient way to provide defaults (or to otherwise
 *    modify) the attrs that have been passed into a component.
 *  — When the component is created in the DOM, we store its DOM element under `this.element`;
 *    this lets us use Cash to modify child DOM state from internal methods via the `this.$()`
 *    method.
 *  — A convenience `component` method, which serves as an alternative to hyperscript and JSX.
 *
 * As with other Mithril components, components extending Component can be initialized
 * and nested using JSX. The `component` method can also
 * be used.
 *
 * @example
 * return m('div', <MyComponent foo="bar"><p>Hello World</p></MyComponent>);
 *
 * @see https://mithril.js.org/components.html
 */

export abstract class Component<
  A extends Attributes = Attributes,
  S = undefined
> implements ClassComponent<A> {
  /**
   * The root DOM element for the component.
   */
  element!: Element;

  /**
   * The attributes passed into the component.
   *
   * @see https://mithril.js.org/components.html#passing-data-to-components
   * @see initAttrs
   */
  attrs!: AttributesCollection<A>;

  /**
   * Class component state that is persisted between redraws.
   *
   * Updating this will **not** automatically trigger a redraw, unlike
   * other frameworks.
   *
   * This is different to Vnode state, which is always an instance of your
   * class component.
   *
   * This is `undefined` by default.
   */
  state!: S;

  /**
   * Used for attribute code completion in JSX.
   * @private
   */
  private __attrs!: A;

  /**
   * @inheritdoc
   */
  abstract view(vnode: Vnode<A, this>): Children;

  /**
   * @inheritdoc
   */
  oninit(vnode: Vnode<A, this>) {
    this.setAttrs(vnode.attrs);
  }

  /**
   * @inheritdoc
   */
  oncreate(vnode: VnodeDOM<A, this>) {
    this.element = vnode.dom;
  }

  /**
   * @inheritdoc
   */
  onbeforeupdate(vnode: VnodeDOM<A, this>) {
    this.setAttrs(vnode.attrs);
  }

  /**
   * @inheritdoc
   */
  onupdate(vnode: VnodeDOM<A, this>) {
  }

  /**
   * @inheritdoc
   */
  onbeforeremove(vnode: VnodeDOM<A, this>) {
  }

  /**
   * @inheritdoc
   */
  onremove(vnode: VnodeDOM<A, this>) {
  }

  /**
   * Saves a reference to the vnode attrs after running them through initAttrs,
   * and checking for common issues.
   *
   * @private
   */
  setAttrs(attributes: A): void {
    this.initAttrs(attributes);

    if (attributes) {
      if ('children' in attributes) {
        // noinspection JSUnresolvedVariable
        throw new Error(
          `[${this.constructor.name}] The "children" attribute of attrs should never be used. Either pass children in as
          the vnode children or rename the attribute`
        );
      }

      if ('tag' in attributes) {
        // noinspection JSUnresolvedVariable
        throw new Error(
          `[${this.constructor.name}] You cannot use the "tag" attribute name with Mithril 2.`
        );
      }
    }

    const attributesCollection = collect<A>(attributes);
    attributesCollection.macro('addClassNames', (...classNames: ClassNames[]) => {
      attributesCollection.put(
        'className',
        classnames(attributesCollection.get('className') as ClassNames, ...classNames)
      );
    });
    this.attrs = attributesCollection as AttributesCollection<A>;
  }

  // noinspection JSUnusedLocalSymbols
  /**
   * Initialize the component's attrs.
   *
   * This can be used to assign default values for missing, optional attrs.
   *
   * @protected
   */
  initAttrs(attributes: A): void {
  }
}
