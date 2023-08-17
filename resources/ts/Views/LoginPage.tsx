import '@maicol07/material-web-additions/card/elevated-card.js';
import '@material/web/button/filled-button.js';
import '@material/web/button/text-button.js';
import '@material/web/checkbox/checkbox.js';
import '@material/web/dialog/dialog.js';
import '@material/web/textfield/filled-text-field.js';

import {
  mdiAccountOutline,
  mdiEmailOutline,
  mdiLockOutline,
  mdiLockQuestion,
  mdiLoginVariant
} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import Page, {PageAttributes} from '@osm/Components/Page';
import {VnodeCollectionItem} from '@osm/typings/jsx';
import {showSnackbar} from '@osm/utils/misc';
import collect from 'collect.js';
import type {
  Vnode
} from 'mithril';
import Stream from 'mithril/stream';
import {
  Form,
  FormSubmitEvent,
  Request,
  RequestError
} from 'mithril-utilities';

export default class LoginPage extends Page {
  form = {
    username: Stream(''),
    password: Stream(''),
    remember: Stream(false)
  };

  forgotPasswordForm = {
    email: Stream('')
  };

  forgotPasswordDialogOpen = false;

  contents(vnode: Vnode<PageAttributes>) {
    return <>
      <h1>{__('Accedi')}</h1>
      <Form id="login" style={{display: 'flex', flexDirection: 'column', gap: '16px'}} state={this.form} onsubmit={this.onLoginFormSubmit.bind(this)}>
        {this.fields().toArray()}
        <div className="login-buttons" style={{gap: '16px'}}>
          {this.buttons().toArray()}
        </div>
      </Form>
      <md-dialog id="forgot-password-dialog" open={this.forgotPasswordDialogOpen} onclosed={this.closeForgotPasswordDialog.bind(this)}>
        <div slot="icon">
          <MdIcon icon={mdiLockQuestion}/>
        </div>
        <div slot="headline">
          <span>{__('Recupero password')}</span>
        </div>
        <div slot="content">
          <p>{__('Inserisci il tuo indirizzo email per ricevere le istruzioni per il recupero della password.')}</p>
          <Form id="forgot-password" onsubmit={this.onForgotPasswordFormSubmit.bind(this)} state={this.forgotPasswordForm}>
            <div style={{textAlign: 'center'}}>
              {this.forgotPasswordFields().toArray()}
            </div>
          </Form>
        </div>
        <div slot="actions">
          <md-text-button onclick={this.onForgotPasswordDialogCancelButtonClicked.bind(this)}>{__('Annulla')}</md-text-button>
          <md-text-button form="forgot-password">{__('Invia')}</md-text-button>
        </div>
      </md-dialog>
    </>;
  }

  fields() {
    return collect<VnodeCollectionItem>({
      username: (
        <md-filled-text-field name="username" required label={__('Nome utente/email')}>
          <MdIcon icon={mdiAccountOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      password: (
        <md-filled-text-field name="password" required label={__('Password')} type="password">
          <MdIcon icon={mdiLockOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      remember: (
        <label>
          <md-checkbox name="remember" touch-target="wrapper"/>
          <span>{__('Ricordami')}</span>
        </label>
      )
    });
  }

  forgotPasswordFields() {
    return collect<VnodeCollectionItem>({
      email: (
        <md-filled-text-field name="email" required label={__('Email')} type="email">
          <MdIcon icon={mdiEmailOutline} slot="leadingicon"/>
        </md-filled-text-field>
      )
    });
  }

  buttons() {
    return collect<VnodeCollectionItem>({
      forgotPassword: (
        <md-text-button type="button" id="forgot-password-button" onclick={this.onForgotPasswordButtonClicked.bind(this)}>
          {__('Password dimenticata')}
          <MdIcon icon={mdiLockQuestion} slot="icon"/>
        </md-text-button>
      ),
      login: (
        <md-filled-button type="submit" id="login-button" style={{float: 'right'}}>
          {__('Accedi')}
          <MdIcon icon={mdiLoginVariant} slot="icon"/>
        </md-filled-button>
      )
    });
  }

  async onLoginFormSubmit(event: FormSubmitEvent) {
    try {
      await Request.get(route('sanctum.csrf-cookie'));
      await Request.post('/login', event.data);
    } catch (error: any) {
      // This.loading.hide();
      void showSnackbar((error as RequestError<{message: string}>).response.message, false);
      return;
    }

    window.location.href = route('dashboard');
  }

  onForgotPasswordButtonClicked() {
    this.openForgotPasswordDialog();
  }

  onForgotPasswordDialogCancelButtonClicked() {
    this.closeForgotPasswordDialog();
  }

  openForgotPasswordDialog() {
    this.forgotPasswordDialogOpen = true;
  }

  closeForgotPasswordDialog() {
    this.forgotPasswordDialogOpen = false;
  }

  async onForgotPasswordFormSubmit(event: FormSubmitEvent) {
    try {
      await Request.post(route('password.email'), event.data);
    } catch (error: any) {
      void showSnackbar((error as RequestError<{message: string}>).response.message, false);
      return;
    }

    void showSnackbar(__('La password è stata inviata alla tua email'));
    this.closeForgotPasswordDialog();
  }
}
