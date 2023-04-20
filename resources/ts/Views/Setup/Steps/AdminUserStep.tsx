import '@material/web/button/filled-button.js';

import {
  mdiAccountOutline,
  mdiChevronRight,
  mdiEmailOutline,
  mdiLockCheckOutline,
  mdiLockOutline
} from '@mdi/js';
import collect from 'collect.js';
import {Vnode} from 'mithril';
import Stream from 'mithril/stream';

import Form, {FormSubmitEvent} from '~/Components/Form';
import MdIcon from '~/Components/MdIcon';
import {VnodeCollectionItem} from '~/typings/jsx';
import {
  SetupStep,
  SetupStepAttributes,
  SetupSteps
} from '~/Views/Setup/Steps/SetupStep';

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
        <Form onsubmit={vnode.attrs.onSaveInstall}>
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
        <md-filled-text-field name="email" label={__('Email')} required state={this.adminUser.email} grid-span={6}>
          <MdIcon icon={mdiEmailOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      username: (
        <md-filled-text-field name="username" label={__('Nome utente')} required state={this.adminUser.username} grid-span={6}>
          <MdIcon icon={mdiAccountOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      password: (
        <md-filled-text-field name="password" label={__('Password')} required type="password" state={this.adminUser.password} minLength={8} grid-span={6}>
          <MdIcon icon={mdiLockOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      password_confirmation: (
        <md-filled-text-field name="password_confirmation" label={__('Conferma password')} required type="password" state={this.adminUser.passwordConfirmation} minLength={8} grid-span={6}>
          <MdIcon icon={mdiLockCheckOutline} slot="leadingicon"/>
        </md-filled-text-field>
      )
    });
  }

  isNextButtonEnabled(): boolean {
    return this.adminUser.username().length > 0 && this.adminUser.password().length > 0 && this.adminUser.password() === this.adminUser.passwordConfirmation();
  }

  nextButton() {
    return (
      <md-filled-button onclick={() => this.element.querySelector('form')?.requestSubmit()} label={__('Salva e installa')} disabled={!this.isNextButtonEnabled()} trailingIcon>
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
