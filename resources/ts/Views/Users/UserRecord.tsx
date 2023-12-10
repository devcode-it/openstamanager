import RecordPage, {RecordPageAttributes} from '@osm/Components/Pages/RecordPage';
import User from '@osm/Models/User';
import {
  Children,
  Vnode
} from 'mithril';

export default class UserRecord extends RecordPage<User> {
  recordType = User;

  contents(vnode: Vnode<RecordPageAttributes<User>>): Children {
    return (
      <>
        {this.backButton(vnode)}
        <h1>{this.record?.username}</h1>
        <code>
          {JSON.stringify(this.record?.attributes)}
        </code>
      </>
    );
  }
}
