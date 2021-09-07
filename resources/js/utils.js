/**
 * Check if class/object A is the same as or a subclass of class B.
 */
export default function subclassOf(A: object, B: object) {
  // noinspection JSUnresolvedVariable
  return A && (A === B || A.prototype instanceof B);
}
