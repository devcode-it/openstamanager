import type MaterialIcons from '@mdi/js';
import {ClassComponent} from 'mithril';

// TODO: Check types
export declare namespace OpenSTAManager {
  export type Modules = Record<string, Module>;

  export interface Module {
    hasBootstrap?: boolean;
    icon: typeof MaterialIcons;
    moduleVendor: string;
  }

  export interface ImportedModule {
    default: ClassComponent;

    [key: string]: ClassComponent | any;
  }

  export interface User {
    picture: string;
    username: string;
    email: string;
  }
}
