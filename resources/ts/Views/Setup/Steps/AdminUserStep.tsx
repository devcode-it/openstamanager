import '@material/web/button/filled-button.js';
import '@material/web/textfield/filled-text-field.js';

import {
  mdiAccountOutline,
  mdiChevronRight,
  mdiEmailOutline,
  mdiLockCheckOutline,
  mdiLockOutline
} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import {VnodeCollectionItem} from '@osm/typings/jsx';
import {
  SetupStep,
  SetupStepAttributes,
  SetupSteps
} from '@osm/Views/Setup/Steps/SetupStep';
import collect from 'collect.js';
import {Vnode} from 'mithril';
import Stream from 'mithril/stream';
import {
  Form,
  FormSubmitEvent
} from 'mithril-utilities';

interface AdminUserStepAttributes extends SetupStepAttributes {
  onSaveInstall: (event: FormSubmitEvent) => void;
}

export default class AdminUserStep extends SetupStep<AdminUserStepAttributes> {
  previousStep = SetupSteps.Database;

  adminUser = {
    email: Stream(''),
    username: Stream(''),
    password: Stream(''),
    passwordConfirmation: Stream('')
  };

  contents(vnode: Vnode<AdminUserStepAttributes>) {
    return (
      <div style={{textAlign: 'center'}}>
        <h4>{__('Utente amministratore')}</h4>
        <p>
          {__('Inserisci le informazioni richieste per creare un utente amministratore per accedere a OpenSTAManager.')}
        </p>
        <Form state={this.adminUser} onsubmit={vnode.attrs.onSaveInstall}>
          <md-layout-grid>
            {this.fields().toArray()}
          </md-layout-grid>
          <input type="submit" hidden/>
        </Form>
      </div>
    );
  }

  fields() {
    return collect<VnodeCollectionItem>({
      email: (
        <md-filled-text-field name="email" label={__('Email')} required grid-span={6}>
          <MdIcon icon={mdiEmailOutline} slot="leading-icon"/>
        </md-filled-text-field>
      ),
      username: (
        <md-filled-text-field name="username" label={__('Nome utente')} required grid-span={6}>
          <MdIcon icon={mdiAccountOutline} slot="leading-icon"/>
        </md-filled-text-field>
      ),
      password: (
        <md-filled-text-field name="password" label={__('Password')} required type="password" minLength={8} grid-span={6}>
          <MdIcon icon={mdiLockOutline} slot="leading-icon"/>
        </md-filled-text-field>
      ),
      password_confirmation: (
        <md-filled-text-field name="passwordConfirmation" label={__('Conferma password')} required type="password" minLength={8} grid-span={6}>
          <MdIcon icon={mdiLockCheckOutline} slot="leading-icon"/>
        </md-filled-text-field>
      )
    });
  }

  isNextButtonEnabled(): boolean {
    return this.adminUser.username().length > 0 && this.adminUser.password().length > 0 && this.adminUser.password() === this.adminUser.passwordConfirmation();
  }

  nextButton() {
    return (
      <md-filled-button onclick={() => this.element.querySelector('form')?.requestSubmit()} disabled={!this.isNextButtonEnabled()} trailing-icon>
        {__('Salva e installa')}
        <MdIcon icon={mdiChevronRight} slot="icon"/>
      </md-filled-button>
    );
  }

  get data(): Record<string, any> {
    return {
      admin_email: this.adminUser.email(),
      admin_username: this.adminUser.username(),
      admin_password: this.adminUser.password(),
      admin_password_confirmation: this.adminUser.passwordConfirmation()
    };
  }
}
