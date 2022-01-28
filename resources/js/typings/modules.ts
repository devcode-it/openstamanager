import {MaterialIcons} from './icons';

export declare namespace OpenSTAManager {
  export type Modules = Record<string, Module>;
  export interface Module {
    hasBootstrap?: boolean;
    icon: MaterialIcons;
    moduleVendor: string;
  }
}
