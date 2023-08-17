import 'mithril-utilities/typings';

import {LayoutGridAttributes} from '@maicol07/material-web-additions/layout-grid/lib/layout-grid';
import {Collection} from 'collect.js';
import Mithril from 'mithril';
import {KebabCasedProperties} from 'type-fest';

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
    'auto-animate'?: boolean
  }
}

declare global {
  // Convert to kebab-case all attributes of HTMLElementTagNameMap
  type HTMLElementTagNameMapKebab = {
    [P in keyof HTMLElementTagNameMap]: KebabCasedProperties<HTMLElementTagNameMap[P]>
  };
  namespace JSX {
    type IntrinsicElements = {
      [tag in keyof HTMLElementTagNameMapKebab]: Omit<Partial<HTMLElementTagNameMapKebab[tag]>, 'style'> & Mithril.Attributes;
    };
  }
}
