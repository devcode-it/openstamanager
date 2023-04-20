import collect from 'collect.js';
import {Children} from 'mithril';

import RecordsTableColumn from '~/Components/DataTable/RecordsTableColumn';
import RecordsPage from '~/Components/Pages/RecordsPage';
import User from '~/Models/User';
import UsersRecordDialog from '~/Views/Users/UsersRecordDialog';

export default class UsersRecords extends RecordsPage<User, any> {
  modelType = User;
  recordDialogType = UsersRecordDialog;
  recordPageRouteName = 'users.show';
  title = __('Utenti');

  tableColumns() {
    return collect<Children>({
      id: <RecordsTableColumn type="numeric" sortable filterable>{__('ID')}</RecordsTableColumn>,
      username: <RecordsTableColumn sortable filterable>{__('Nome utente')}</RecordsTableColumn>,
      email: <RecordsTableColumn sortable filterable>{__('Email')}</RecordsTableColumn>,
      createdAt: <RecordsTableColumn sortable>{__('Creato il')}</RecordsTableColumn>,
      updatedAt: <RecordsTableColumn sortable>{__('Aggiornato il')}</RecordsTableColumn>
    });
  }
}
