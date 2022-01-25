import {MaterialIcons} from '../types';

declare module OpenSTAManager {
  export type Modules = Record<string, Module>;
  export interface Module {
    hasBootstrap?: boolean;
    icon: MaterialIcons;
  }
}
