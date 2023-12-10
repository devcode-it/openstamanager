import Record from '@osm/Models/Record';
import {Attr, Model} from 'spraypaint';

@Model()
export default class User extends Record {
  static jsonapiType = 'users';

  @Attr() username!: string;
  @Attr() email!: string;
}
