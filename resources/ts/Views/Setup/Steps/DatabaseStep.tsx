import '@maicol07/material-web-additions/layout-grid/layout-grid.js';
import '@material/web/textfield/filled-text-field.js';

import {
  mdiAccountOutline,
  mdiDatabaseOutline,
  mdiFormTextboxPassword,
  mdiPowerPlugOutline,
  mdiServerNetwork,
  mdiTestTube
} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import {VnodeCollectionItem} from '@osm/typings/jsx';
import {showSnackbar} from '@osm/utils/misc';
import collect from 'collect.js';
import {
  Children,
  Vnode
} from 'mithril';
import Stream from 'mithril/stream';
import {
  Form,
  Request,
  RequestError
} from 'mithril-utilities';

import {
  SetupStep,
  SetupStepAttributes,
  SetupSteps
} from './SetupStep';

export default class DatabaseStep extends SetupStep {
  previousStep = SetupSteps.RegionalSettings;
  nextStep = SetupSteps.AdminUser;

  database = {
    driver: Stream('mysql'),
    host: Stream(''),
    username: Stream(''),
    password: Stream(''),
    database_name: Stream(''),
    port: Stream('3306')
  };

  contents() {
    return (
      <div>
        <h4>{__('Database')}</h4>
        <p>{__('Inserisci le informazioni per connetterti al database MySQL. I campi sono già compilati con un esempio. Se non sai come procedere, contatta il tuo fornitore di hosting.')}</p>
        <Form>
          <md-layout-grid>
            {this.fields().toArray()}
          </md-layout-grid>
        </Form>
        <small style="display: block; margin-top: 16px;">{__('* Campi obbligatori')}</small>
      </div>
    );
  }

  fields() {
    return collect<VnodeCollectionItem>({
      // TODO: Autocomplete/Select with possible drivers
      driver: (
        <md-filled-text-field name="driver" label={__('Driver')} required state={this.database.driver} grid-span={4}>
          <MdIcon icon={mdiPowerPlugOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      host: (
        <md-filled-text-field name="host" label={__('Host')} required state={this.database.host} grid-span={4} placeholder="localhost">
          <MdIcon icon={mdiServerNetwork} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      username: (
        <md-filled-text-field name="username" label={__('Nome utente')} required state={this.database.username} grid-span={4} placeholder="root">
          <MdIcon icon={mdiAccountOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      password: (
        <md-filled-text-field type="password" name="password" label={__('Password')} state={this.database.password} grid-span={4}>
          <MdIcon icon={mdiFormTextboxPassword} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      database_name: (
        <md-filled-text-field name="database_name" label={__('Nome database')} required state={this.database.database_name} grid-span={4} placeholder="openstamanager">
          <MdIcon icon={mdiDatabaseOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      port: (
        <md-filled-text-field name="port" label={__('Porta')} required state={this.database.port} grid-span={4} placeholder="3306">
          <MdIcon icon={mdiServerNetwork} slot="leadingicon"/>
        </md-filled-text-field>
      )
    });
  }

  middleButton(vnode: Vnode<SetupStepAttributes, this>): Children {
    return (
      <md-outlined-button id="test-db" onclick={this.onTestButtonClicked.bind(this)} grid-span={4}>
        {__('Testa il database')}
        <MdIcon icon={mdiTestTube} slot="icon"/>
      </md-outlined-button>
    );
  }

  async onTestButtonClicked() {
    await this.testDatabase();
  }

  async testDatabase(silentSuccess = false, silentError = false): Promise<boolean> {
    try {
      await Request.post(route('setup.test'), this.data);
    } catch (error: any) {
      if (!silentError) {
        void showSnackbar(__('Si è verificato un errore durante la connessione al database: :error', {
          error: (error as RequestError<{message: string}>).response.message
        }));
      }

      return false;
    }

    if (!silentSuccess) {
      void showSnackbar(__('Connessione al database riuscita'));
    }

    return true;
  }

  async onNextButtonClicked(vnode: Vnode<SetupStepAttributes, this>) {
    const testResult = await this.testDatabase();
    if (testResult) {
      return super.onNextButtonClicked(vnode);
    }
  }

  get data(): Record<string, any> {
    return {
      database_driver: this.database.driver(),
      database_host: this.database.host(),
      database_username: this.database.username(),
      database_password: this.database.password(),
      database_name: this.database.database_name(),
      database_port: this.database.port()
    };
  }
}
