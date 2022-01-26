// noinspection JSUnusedGlobalSymbols

declare module 'mithril-node-render' {
  import m from 'mithril';

  export const escapeText: string;
  export const escapeAttribute: string;
  export default function render(vnode: m.Vnode): string;
  export function sync(vnode: m.Vnode): string;
}
