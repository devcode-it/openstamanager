import {Collection} from 'collect.js';
import type * as CSS from 'csstype';
import Mithril from 'mithril';

export type VnodeCollectionItem = Record<string, Mithril.Vnode>;
export type VnodeCollection = Collection<VnodeCollectionItem>;

declare module 'csstype' {
  interface Properties {
    // Allow namespaced CSS Custom Properties
    [index: `--md-${string}`]: any;

    [index: `--mdc-${string}`]: any;
  }
}

declare module 'mithril' {
  interface Attributes extends LayoutGridAttributes {
    // Needed for md-dialog
    dialogAction?: string | 'ok' | 'discard' | 'close' | 'cancel' | 'accept' | 'decline',
    style?: string | Partial<CSS.Properties> | Partial<CSSStyleDeclaration>,
    autoAnimate?: boolean
  }
}
