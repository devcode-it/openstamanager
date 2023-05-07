/**
 * @source https://github.com/flarum/core/blob/master/js/src/common/extend.js
 */

/**
 * Type that returns an array of all keys of a provided object that are of
 * the provided type, or a subtype of the type.
 */
declare type KeysOfType<Type extends object, Match> = {
  [Key in keyof Type]-?: Type[Key] extends Match ? Key : never;
};

/**
 * Type that matches one of the keys of an object that is of the provided
 * type, or a subtype of it.
 */
declare type KeyOfType<Type extends object, Match> = KeysOfType<Type, Match>[keyof Type];

/**
 * Extend an object's method by running its output through a mutating callback
 * every time it is called.
 *
 * The callback accepts the method's return value and should perform any
 * mutations directly on this value. For this reason, this function will not be
 * effective on methods, which return scalar values (numbers, strings, booleans).
 *
 * Care should be taken to extend the correct object — usually, a class'
 * prototype will be the desired target of extension, not the class itself.
 *
 * @example <caption>Example usage of extending one method.</caption>
 * extend(Discussion.prototype, 'badges', function(badges) {
 *   // do something with `badges`
 * });
 *
 * @example <caption>Example usage of extending multiple methods.</caption>
 * extend(IndexPage.prototype, ['oncreate', 'onupdate'], function(vnode) {
 *   // something that needs to be run on creation and update
 * });
 *
 * @param object The object that owns the method
 * @param methods The name or names of the method(s) to extend
 * @param callback A callback, which mutates the method's output.
 */
export function extend<T extends Record<string, any>, K extends KeyOfType<T, Function>>(
  object: T,
  methods: K | K[],
  callback: (this: T, value: ReturnType<T[K]>, ...arguments_: Parameters<T[K]>) => void
) {
  const allMethods = Array.isArray(methods) ? methods : [methods];

  for (const method of allMethods) {
    const original: Function | undefined = object[method];

    object[method] = function (this: T, ...arguments_: Parameters<T[K]>): any {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
      const value = original ? original.apply(this, arguments_) : undefined;

      Reflect.apply(callback, this, [value, ...arguments_]);

      return value;
    } as T[K];

    Object.assign(object[method], original);
  }
}

/**
 * Override an object's method by replacing it with a new function, so that the
 * new function will be run every time the object's method is called.
 *
 * The replacement function accepts the original method as its first argument,
 * which is like a call to `super`. Any arguments passed to the original method
 * are also passed to the replacement.
 *
 * Care should be taken to extend the correct object – in most cases, a class'
 * prototype will be the desired target of extension, not the class itself.
 *
 * @example <caption>Example usage of overriding one method.</caption>
 * override(Discussion.prototype, 'badges', function(original) {
 *   const badges = original();
 *   // do something with badges
 *   return badges;
 * });
 *
 * @example <caption>Example usage of overriding multiple methods.</caption>
 * extend(Discussion.prototype, ['oncreate', 'onupdate'], function(original, vnode) {
 *   // something that needs to be run on creation and update
 * });
 *
 * @param object The object that owns the method
 * @param methods The name or names of the method(s) to override
 * @param newMethod The method to replace it with.
 */
export function override<T extends Record<any, any>, K extends KeyOfType<T, Function>>(
  object: T,
  methods: K | K[],
  newMethod: (this: T, orig: T[K], ...arguments_: Parameters<T[K]>) => void
) {
  const allMethods = Array.isArray(methods) ? methods : [methods];

  for (const method of allMethods) {
    const original: Function = object[method];

    object[method] = function (this: T, ...arguments_: Parameters<T[K]>): any {
      return Reflect.apply(newMethod, this, [original.bind(this), ...arguments_]);
    } as T[K];

    Object.assign(object[method], original);
  }
}
