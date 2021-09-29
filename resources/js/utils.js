// noinspection JSUnusedGlobalSymbols

/**
 * Check if class/object A is the same as or a subclass of class B.
 */
export function subclassOf(A: {...}, B: {...}): boolean {
  // noinspection JSUnresolvedVariable
  return A && (A === B || A.prototype instanceof B);
}

/**
 * Check if a string contains HTML code/tags
 */
export function containsHTML(string_: string): boolean {
  // eslint-disable-next-line unicorn/better-regex
  return /<[a-z][\s\S]*>/i.test(string_);
}
