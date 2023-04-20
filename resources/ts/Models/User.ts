import Model, {
  ModelAttributes,
  ModelRelations
} from '~/Models/Model';

export interface UserAttributes extends ModelAttributes {
  username: string;
  email: string;
}

export interface UserRelations extends ModelRelations {
  // notifications: DatabaseNotifications
}

export default class User extends Model<UserAttributes, UserRelations> {
}
