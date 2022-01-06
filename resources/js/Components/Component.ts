import classnames, {Argument as ClassNames} from 'classnames';
import collect, {Collection} from 'collect.js';
import m, {
  Children,
  ClassComponent,
  Vnode,
  VnodeDOM
} from 'mithril';

interface Attributes<T> extends Collection<T> {
  addClassNames(...classNames: ClassNames[]): void
  addStyles(...styles: string[]): void
}
// noinspection SpellCheckingInspection,JSUnusedGlobalSymbols

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
 * @see https://js.org/components.html
 */

export default class Component<A> implements m.Component<A>, ClassComponent<A> {
  /**
   * The root DOM element for the component.
   *
   * @protected
   */
  element: Element;

  /**
   * The attributes passed into the component. They are transformed into a collection by initAttrs.
   *
   * @method <string> addClassNames()
   *
   * @see https://js.org/components.html#passing-data-to-components
   * @see initAttrs
   *
   * @protected
   */
  attrs: Attributes<string>;

  constructor() {
    this.element = undefined as unknown as Element;
    this.attrs = undefined as unknown as Attributes<string>;
  }

  /**
   * @inheritdoc
   * @abstract
   */
  view(vnode: Vnode<A>): Children {
    return m('div');
  }

  /**
   * @inheritdoc
   */
  oninit(vnode: Vnode<A>) {
    this.setAttrs(vnode.attrs);
  }

  /**
   * @inheritdoc
   */
  oncreate(vnode: VnodeDOM<A>) {
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
  onupdate(vnode: VnodeDOM<A>) {}

  /**
   * @inheritdoc
   */
  onbeforeremove(vnode: VnodeDOM<A>) {}

  /**
   * @inheritdoc
   */
  onremove(vnode: VnodeDOM<A>) {}

  /**
   * Saves a reference to the vnode attrs after running them through initAttrs,
   * and checking for common issues.
   *
   * @private
   */
  setAttrs(attributes: {} = {}): void {
    this.initAttrs(attributes);

    if (attributes) {
      if ('children' in attributes) {
        // noinspection JSUnresolvedVariable
        throw new Error(
          `[${this.constructor.name}] The "children" attribute of attrs should never be used. Either pass children in as the vnode children or rename the attribute`
        );
      }

      if ('tag' in attributes) {
        // noinspection JSUnresolvedVariable
        throw new Error(
          `[${this.constructor.name}] You cannot use the "tag" attribute name with Mithril 2.`
        );
      }
    }

    const attributesCollection: Collection<string> = collect(attributes);
    attributesCollection.macro('addClassNames', (...classNames: ClassNames[]) => {
      attributesCollection.put(
        'className',
        classnames(attributesCollection.get('className') as ClassNames, ...classNames)
      );
    });
    attributesCollection.macro('addStyles', (...styles: string[]) => {
      let s: string = attributesCollection.get<string, string>('style', '' as unknown as () => string) as string; // Type conversions are required here because of the way the typescript compiler works.

      if (!s.trimEnd().endsWith(';')) {
        s += '; ';
      }

      s += styles.join('; ');
      attributesCollection.put('style', s);
    });
    this.attrs = attributesCollection as Attributes<string>;
  }

  // noinspection JSUnusedLocalSymbols
  /**
   * Initialize the component's attrs.
   *
   * This can be used to assign default values for missing, optional attrs.
   *
   * @protected
   */
  initAttrs(attributes: {}): void {}
}
