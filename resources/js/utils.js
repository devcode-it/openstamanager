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

/**
 * Show a snackbar
 */
export async function showSnackbar(message: string, duration: number = 5000, acceptText = 'OK', cancelText: ?string): Promise<boolean> {
  const snackbar = document.createElement('mwc-snackbar');
  snackbar.labelText = message;
  snackbar.timeoutMs = duration;
  if (acceptText) {
    const button = document.createElement('mwc-button');
    button.label = acceptText;
    button.slot = 'action';
    snackbar.append(button);
  }
  if (cancelText) {
    const button = document.createElement('mwc-button');
    button.label = cancelText;
    button.slot = 'cancel';
    snackbar.append(button);
  }
  document.body.append(snackbar);

  // eslint-disable-next-line unicorn/consistent-function-scoping
  const response = (value?: boolean) => value;

  // noinspection JSUnusedLocalSymbols
  const reasonPromise = new Promise((resolve, reject) => {});
  snackbar.addEventListener('MDCSnackbar:closed', (event) => {
    response(event?.detail?.reason === 'action' ?? false);
  });
  snackbar.show();
  snackbar.addEventListener('MDCSnackbar:closed', () => {
    snackbar.remove();
  });
  return reasonPromise;
}
