import '@material/web/textfield/filled-text-field.js';

import {
  mdiAccountOutline,
  mdiEmailOutline
} from '@mdi/js';
import AddEditRecordDialog from '@osm/Components/Dialogs/AddEditRecordDialog';
import MdIcon from '@osm/Components/MdIcon';
import User from '@osm/Models/User';
import collect, {Collection} from 'collect.js';
import {Children} from 'mithril';
import Stream from 'mithril/stream';

export default class UsersRecordDialog extends AddEditRecordDialog<User> {
  modelType = User;
  numberOfColumns = 2;
  formState: Map<string, Stream<any>> = UsersRecordDialog.createFormState({
    username: Stream(),
    email: Stream()
  });

  fields(): Collection<Children> {
    return collect({
      username: (
        <md-filled-text-field required label={__('Nome utente')}>
          <MdIcon icon={mdiAccountOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      email: (
        <md-filled-text-field required type="email" label={__('Email')}>
          <MdIcon icon={mdiEmailOutline} slot="leadingicon"/>
        </md-filled-text-field>
      )
    });
  }

  async save() {
    // if (this.record.isNew()) {
    //   this.record.password = 'default';
    // }
    return super.save();
  }
}
