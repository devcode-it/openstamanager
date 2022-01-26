import {MaterialIcons} from './icons';

export declare module OpenSTAManager {
  export type Modules = Record<string, Module>;
  export interface Module {
    hasBootstrap?: boolean;
    icon: MaterialIcons;
  }
}
