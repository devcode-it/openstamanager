import RecordsTableColumn from '@osm/Components/DataTable/RecordsTableColumn';
import RecordsPage from '@osm/Components/Pages/RecordsPage';
import User from '@osm/Models/User';
import UsersRecordDialog from '@osm/Views/Users/UsersRecordDialog';
import collect from 'collect.js';
import {Children} from 'mithril';

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
