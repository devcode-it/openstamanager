import {
  Children,
  Vnode
} from 'mithril';

import RecordPage, {RecordPageAttributes} from '~/Components/Pages/RecordPage';
import User from '~/Models/User';

export default class UserRecord extends RecordPage<User> {
  recordType = User;

  contents(vnode: Vnode<RecordPageAttributes<User>>): Children {
    return (
      <>
        {this.backButton(vnode)}
        <h1>{this.record?.getAttribute('username')}</h1>
        <code>
          {JSON.stringify(this.record?.getAttributes())}
        </code>
      </>
    );
  }
}
