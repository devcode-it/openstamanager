import 'mithril-utilities/typings';

import {LayoutGridAttributes} from '@maicol07/material-web-additions/layout-grid/lib/layout-grid';
import {Collection} from 'collect.js';
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
    autoAnimate?: boolean
  }
}
