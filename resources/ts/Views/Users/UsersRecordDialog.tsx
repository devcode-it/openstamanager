import '@osm/Components/m3/FilledTextField';

import {
  mdiAccountOutline,
  mdiEmailOutline
} from '@mdi/js';
import AddEditRecordDialog from '@osm/Components/Dialogs/AddEditRecordDialog';
import MdIcon from '@osm/Components/MdIcon';
import User, {UserAttributes} from '@osm/Models/User';
import {JSONAPI} from '@osm/typings/request';
import {showSnackbar} from '@osm/utils/misc';
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
    if (this.record.isNew()) {
      this.record.setAttribute('password', 'default');
    }

    this.record.setAttributes(this.formStateRecord as UserAttributes);
    try {
      const response = await this.record.save();
      const responseModel = response.getModel() as User;
      if (responseModel !== undefined) {
        this.record = responseModel;
        void showSnackbar(__('Record salvato con successo'));
      }
      return response.getModelId() !== undefined;
    } catch (error) {
      const message = (error as JSONAPI.RequestError).response.errors.map((error_) => error_.detail).join('; ');
      void showSnackbar(message, false);
      return false;
    }
  }
}
