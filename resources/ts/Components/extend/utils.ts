import {extend} from '@osm/Components/extend/extend';
import Drawer, {DrawerAttributes} from '@osm/Components/layout/Drawer';
import {VnodeCollectionItem} from '@osm/typings/jsx';
import {Collection} from 'collect.js';

// eslint-disable-next-line import/prefer-default-export
export function manageDrawerEntries(callback: (this: Drawer, value: Collection<VnodeCollectionItem>) => Collection<VnodeCollectionItem>): void {
  extend(Drawer.prototype as Drawer<DrawerAttributes>, 'entries', callback);
}
