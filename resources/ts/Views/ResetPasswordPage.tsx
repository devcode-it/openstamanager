import '@maicol07/material-web-additions/card/elevated-card.js';
import '@material/web/button/filled-button.js';
import '@osm/Components/m3/FilledTextField';

import {router} from '@maicol07/inertia-mithril';
import {
  mdiAccountOutline,
  mdiLockCheckOutline,
  mdiLockOutline
} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import Page, {PageAttributes} from '@osm/Components/Page';
import {VnodeCollectionItem} from '@osm/typings/jsx';
import {showSnackbar} from '@osm/utils/misc';
import collect from 'collect.js';
import type {Vnode} from 'mithril';
import Stream from 'mithril/stream';
import {
  Form,
  FormSubmitEvent,
  Request,
  RequestError
} from 'mithril-utilities';

export default class ResetPasswordPage extends Page {
  form = {
    password: Stream(''),
    password_confirmation: Stream('')
  };

  parameters!: URLSearchParams;

  oninit(vnode: Vnode<PageAttributes, this>) {
    super.oninit(vnode);
    this.parameters = new URLSearchParams(window.location.search);
  }

  contents(vnode: Vnode<PageAttributes>) {
    return <>
      <h1>{__('Reimposta password')}</h1>
      <p>{__('Inserisci la nuova password per accedere a OpenSTAManager.')}</p>
      <Form id="login" style={{display: 'flex', flexDirection: 'column', gap: '16px'}} onsubmit={this.onResetPasswordFormSubmit.bind(this)} state={this.form}>
        {this.fields().toArray()}
      </Form>
      <div className="login-buttons" style={{gap: '16px'}}>
        {this.buttons().toArray()}
      </div>
    </>;
  }

  fields() {
    return collect<VnodeCollectionItem>({
      email: <input type="hidden" name="email" value={this.parameters.get('email')}/>,
      token: <input type="hidden" name="token" value={this.parameters.get('token')}/>,
      password: (
        <md-filled-text-field name="password" required label={__('Password')} type="password">
          <MdIcon icon={mdiLockOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      password_confirmation: (
        <md-filled-text-field name="password_confirmation" required label={__('Conferma password')} type="password">
          <MdIcon icon={mdiLockCheckOutline} slot="leadingicon"/>
        </md-filled-text-field>
      )
    });
  }

  buttons() {
    return collect<VnodeCollectionItem>({
      login: (
        <md-filled-button type="submit" onclick={this.onResetPasswordButtonClicked.bind(this)}>
          {__('Reimposta password')}
          <MdIcon icon={mdiAccountOutline} slot="leadingicon"/>
        </md-filled-button>
      )
    });
  }

  onResetPasswordButtonClicked() {
    this.element.querySelector('form')?.requestSubmit();
  }

  async onResetPasswordFormSubmit(event: FormSubmitEvent) {
    try {
      await Request.post(route('password.update'), event.data);
    } catch (error: any) {
      void showSnackbar((error as RequestError<{message: string}>).response.message, false);
      return;
    }

    void showSnackbar(__('Reset della password effettuato con successo. Puoi ora accedere.'));
    router.visit(route('login'));
  }
}
