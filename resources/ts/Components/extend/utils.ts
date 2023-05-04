import {Collection} from 'collect.js';

import {extend} from '~/Components/extend/extend';
import Drawer, {DrawerAttributes} from '~/Components/layout/Drawer';
import {VnodeCollectionItem} from '~/typings/jsx';

// eslint-disable-next-line import/prefer-default-export
export function manageDrawerEntries(callback: (this: Drawer, value: Collection<VnodeCollectionItem>) => Collection<VnodeCollectionItem>): void {
  extend(Drawer.prototype as Drawer<DrawerAttributes>, 'entries', callback);
}
