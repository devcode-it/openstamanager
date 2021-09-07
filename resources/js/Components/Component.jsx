import classnames, {Argument as ClassNames} from 'classnames';
import collect, {Collection} from 'collect.js';
import m, {
  Children, ClassComponent,
  Vnode, VnodeDOM
} from 'mithril';

interface Attributes extends Collection {
  addClassNames(...classNames: ClassNames[]): void,
  addStyles(...styles: string[]): void
}

// noinspection SpellCheckingInspection
/**
 * @abstract
 *
 * The `Component` class defines a user interface 'building block'. A component
 * generates a virtual DOM to be rendered on each redraw.
 *
 * Essentially, this is a wrapper for Mithril's components that adds several useful features:
 *
 *  - In the `oninit` and `onbeforeupdate` lifecycle hooks, we store vnode attrs in `this.attrs.
 *    This allows us to use attrs across components without having to pass the vnode to every single
 *    method.
 *  - The static `initAttrs` method allows a convenient way to provide defaults (or to otherwise
 *    modify) the attrs that have been passed into a component.
 *  - When the component is created in the DOM, we store its DOM element under `this.element`;
 *    this lets us use jQuery to modify child DOM state from internal methods via the `this.$()`
 *    method.
 *  - A convenience `component` method, which serves as an alternative to hyperscript and JSX.
 *
 * As with other Mithril components, components extending Component can be initialized
 * and nested using JSX, hyperscript, or a combination of both. The `component` method can also
 * be used.
 *
 * @example
 * return m('div', <MyComponent foo="bar"><p>Hello World</p></MyComponent>);
 *
 * @example
 * return m('div', MyComponent.component({foo: 'bar'), m('p', 'Hello World!'));
 *
 * @see https://js.org/components.html
 */
export default class Component implements ClassComponent {
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
  attrs: Attributes;

  /**
   * @inheritdoc
   * @abstract
   */
  view(vnode: Vnode): Children {}

  /**
   * @inheritdoc
   */
  oninit(vnode: Vnode) {
    this.setAttrs(vnode.attrs);
  }

  /**
   * @inheritdoc
   */
  oncreate(vnode: VnodeDOM) {
    this.element = vnode.dom;
  }

  /**
   * @inheritdoc
   */
  onbeforeupdate(vnode: VnodeDOM) {
    this.setAttrs(vnode.attrs);
  }

  /**
   * @inheritdoc
   */
  onupdate(vnode: VnodeDOM) {}

  /**
   * @inheritdoc
   */
  onbeforeremove(vnode: VnodeDOM) {}

  /**
   * @inheritdoc
   */
  onremove(vnode: VnodeDOM) {}

  /**
   * Returns a jQuery object for this component's element. If you pass in a
   * selector string, this method will return a jQuery object, using the current
   * element as its buffer.
   *
   * For example, calling `component.$('li')` will return a jQuery object
   * containing all of the `li` elements inside the DOM element of this
   * component.
   *
   * @param [selector] a jQuery-compatible selector string
   * @returns the jQuery object for the DOM node
   * @final
   * @protected
   */
  $(selector?: string): JQuery {
    const $element: JQuery<HTMLElement> = $(this.element);
    return selector ? $element.find((element) => selector(element)) : $element;
  }


  /**
   * Convenience method to attach a component without JSX.
   * Has the same effect as calling `m(THIS_CLASS, attrs, children)`.
   *
   * @see https://js.org/hyperscript.html#mselector,-attributes,-children
   */
  static component(attributes = {}, children): Vnode {
    const componentAttributes: Record<string, any> = { ...attributes};

    return m(this, componentAttributes, children);
  }

  /**
   * Saves a reference to the vnode attrs after running them through initAttrs,
   * and checking for common issues.
   *
   * @private
   */
  setAttrs(attributes: Object = {}): void {
    this.initAttrs(attributes);
    if (attributes) {
      if ('children' in attributes) {
        // noinspection JSUnresolvedVariable
        throw new Error(`[${this.constructor.name}] The "children" attribute of attrs should never be used. Either pass children in as the vnode children or rename the attribute`);
      }
      if ('tag' in attributes) {
        // noinspection JSUnresolvedVariable
        throw new Error(`[${this.constructor.name}] You cannot use the "tag" attribute name with Mithril 2.`);
      }
    }
    this.attrs = collect(attributes);
    this.attrs.macro('addClassNames', (...classNames: ClassNames[]) => {
      this.attrs.put('className', classnames(this.attrs.get('className'), ...classNames));
    });
    this.attrs.macro('addStyles', (...styles: string[]) => {
      let s: string = this.attrs.get('style', '');

      if (!s.trimEnd().endsWith(';')) {
        s += '; ';
      }

      s += styles.join('; ');
      this.attrs.put('style', s);
    });
  }

  /**
   * Initialize the component's attrs.
   *
   * This can be used to assign default values for missing, optional attrs.
   *
   * @protected
   */
  initAttrs(attributes: Object): void {}
}
