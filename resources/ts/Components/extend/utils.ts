import {Page} from '../Page';

/**
 * Add a new page namespace.
 */
export function addPageNamespace(namespace: string) {
  window.InertiaPlugin.addNamespace(namespace, async (name) => {
    const baseModulePath = `${window.location.origin}/modules/${namespace}/Views`;
    const bundledPages = import.meta.glob(`${baseModulePath}/**/*.js`) as Record<string, () => Promise<{default: Page}>>;
    const page = bundledPages[`${baseModulePath}/${name}.tsx`];
    return (await page()).default as Page;
  });
}
