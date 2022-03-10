import {ClassComponent} from 'mithril';

import {MaterialIcons} from './icons';

export declare namespace OpenSTAManager {
  export type Modules = Record<string, Module>;

  export interface Module {
    hasBootstrap?: boolean;
    icon: MaterialIcons;
    moduleVendor: string;
  }

  export interface ImportedModule {
    default: ClassComponent | any;
    bootstrap?: Function;

    [key: string]: ClassComponent | any;
  }

  export interface User {
    picture: string;
    username: string;
    email: string;
  }
}
