import '@maicol07/material-web-additions/card/elevated-card.js';
import '@material/web/button/filled-button.js';
import '@material/web/button/text-button.js';
import '@material/web/checkbox/checkbox.js';
import '@material/web/dialog/dialog.js';
import '~/Components/m3/FilledTextField';

import {Dialog} from '@material/web/dialog/lib/dialog';
import {
  mdiAccountOutline,
  mdiEmailOutline,
  mdiLockOutline,
  mdiLockQuestion,
  mdiLoginVariant
} from '@mdi/js';
import collect from 'collect.js';
import type {
  Vnode,
  VnodeDOM
} from 'mithril';
import Stream from 'mithril/stream';

import Form, {FormSubmitEvent} from '~/Components/Form';
import MdIcon from '~/Components/MdIcon';
import Page, {
  PageAttributes
} from '~/Components/Page';
import {VnodeCollectionItem} from '~/typings/jsx';
import {showSnackbar} from '~/utils/misc';
import Request, {
  RequestError
} from '~/utils/Request';

export default class LoginPage extends Page {
  form = {
    username: Stream(''),
    password: Stream(''),
    remember: Stream(false)
  };

  forgotPasswordForm = {
    email: Stream('')
  };

  forgotPasswordDialog!: Dialog;

  contents(vnode: Vnode<PageAttributes>) {
    return <>
      <h1>{__('Accedi')}</h1>
      <Form id="login" style={{display: 'flex', flexDirection: 'column', gap: '16px'}} onsubmit={this.onLoginFormSubmit.bind(this)}>
        {this.fields().toArray()}
        <div className="login-buttons" style={{gap: '16px'}}>
          {this.buttons().toArray()}
        </div>
      </Form>
      <md-dialog id="forgot-password-dialog">
        <h2 slot="headline">{__('Recupero password')}</h2>
        <p>{__('Inserisci il tuo indirizzo email per ricevere le istruzioni per il recupero della password.')}</p>
        <Form id="forgot-password" onsubmit={this.onForgotPasswordFormSubmit.bind(this)} state={this.forgotPasswordForm}>
          <div style={{textAlign: 'center'}}>
            {this.forgotPasswordFields().toArray()}
          </div>
        </Form>
        <md-text-button dialogAction="cancel" slot="footer" label={__('Annulla')}></md-text-button>
        <md-filled-button slot="footer" label={__('Invia')} onclick={this.onForgotPasswordDialogSubmitButtonClicked.bind(this)}></md-filled-button>
      </md-dialog>
    </>;
  }

  fields() {
    return collect<VnodeCollectionItem>({
      username: (
        <md-filled-text-field name="username" required label={__('Nome utente/email')} state={this.form.username}>
          <MdIcon icon={mdiAccountOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      password: (
        <md-filled-text-field name="password" required label={__('Password')} type="password" state={this.form.password}>
          <MdIcon icon={mdiLockOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      remember: (
        <label>
          <md-checkbox name="remember" state={this.form.remember}/>
          <span>{__('Ricordami')}</span>
        </label>
      )
    });
  }

  forgotPasswordFields() {
    return collect<VnodeCollectionItem>({
      email: (
        <md-filled-text-field name="email" required label={__('Email')} type="email" state={this.forgotPasswordForm.email}>
          <MdIcon icon={mdiEmailOutline} slot="leadingicon"/>
        </md-filled-text-field>
      )
    });
  }

  buttons() {
    return collect<VnodeCollectionItem>({
      forgotPassword: (
        <md-text-button id="forgot-password-button" label={__('Password dimenticata')} onclick={this.onForgotPasswordButtonClicked.bind(this)}>
          <MdIcon icon={mdiLockQuestion} slot="icon"/>
        </md-text-button>
      ),
      login: (
        <md-filled-button type="submit" id="login-button" label={__('Accedi')} style={{float: 'right'}}>
          <MdIcon icon={mdiLoginVariant} slot="icon"/>
        </md-filled-button>
      )
    });
  }

  oncreate(vnode: VnodeDOM<PageAttributes, this>) {
    super.oncreate(vnode);

    this.forgotPasswordDialog = this.element.querySelector<Dialog>('md-dialog#forgot-password-dialog')!;
  }

  async onLoginFormSubmit(event: FormSubmitEvent) {
    try {
      await Request.get(route('sanctum.csrf-cookie'));
      await Request.post('/login', event.data);
    } catch (error: any) {
      // this.loading.hide();
      void showSnackbar((error as RequestError).response.message, false);
      return;
    }

    window.location.href = route('dashboard');
  }

  onForgotPasswordButtonClicked() {
    this.forgotPasswordDialog.show();
  }

  onForgotPasswordDialogSubmitButtonClicked() {
    this.forgotPasswordDialog.querySelector('form')?.requestSubmit();
  }

  async onForgotPasswordFormSubmit(event: FormSubmitEvent) {
    try {
      await Request.post(route('password.email'), event.data);
    } catch (error: any) {
      void showSnackbar((error as RequestError).response.message, false);
      return;
    }

    void showSnackbar(__('La password è stata inviata alla tua email'));
    this.forgotPasswordDialog.close();
  }
}