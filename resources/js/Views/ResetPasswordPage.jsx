// noinspection DuplicatedCode

import '@maicol07/mwc-card';
import '@maicol07/mwc-layout-grid';
import '../WebComponents/TextField';

import {Inertia} from '@inertiajs/inertia';
import type {TextField} from '@material/mwc-textfield';
import type {Cash} from 'cash-dom';
import redaxios from 'redaxios';

// eslint-disable-next-line import/no-absolute-path
import logoUrl from '/images/logo_completo.png';

import LoadingButton from '../Components/LoadingButton.jsx';
import Mdi from '../Components/Mdi.jsx';
import Page from '../Components/Page.jsx';
import {
  getFormData,
  isFormValid,
  showSnackbar
} from '../utils';

export default class ResetPasswordPage extends Page {
  loading: Cash;
  parameters: URLSearchParams;

  oninit(vnode) {
    super.oninit(vnode);

    this.parameters = new URLSearchParams(window.location.search);
  }

  view(vnode) {
    return (
      <mwc-card outlined className="center ext-container ext-container-small">
        <img src={logoUrl} className="center stretch" alt={__('OpenSTAManager')}/>
        <form id="reset-password" style="padding: 16px; text-align: center;">
          <h3 style="margin-top: 0;">{__('Reimposta password')}</h3>
          <input hidden id="email" name="email" value={this.parameters.get('email')}/>
          <input hidden id="token" name="token" value={this.parameters.get('token')}/>
          <text-field label={__('Password')} id="password" name="password" required type="password">
            <Mdi icon="lock-outline" slot="icon"/>
          </text-field>
          <text-field label={__('Conferma password')} id="password_confirm" name="password_confirm" type="password" required style="margin-top: 16px;">
            <Mdi icon="repeat-variant" slot="icon"/>
          </text-field>
          <LoadingButton
            type="submit"
            raised
            id="reset-password-button"
            label={__('Resetta password')}
            icon="lock-reset"
            style="float: right;"
            onclick={this.onResetPasswordButtonClicked.bind(this)}
          />
        </form>
      </mwc-card>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.loading = $(this.element).find('#reset-password mwc-circular-progress');
  }

  async onResetPasswordButtonClicked(event: PointerEvent) {
    event.preventDefault();
    this.loading.show();

    const form = $(this.element).find('#reset-password');
    const password: TextField = form.find('#password').get(0);
    const passwordConfirm: TextField = form.find('#password_confirm').get(0);

    passwordConfirm.setCustomValidity(
      password.value !== passwordConfirm.value ? __('Le password non corrispondono') : ''
    );

    if (!isFormValid(form)) {
      this.loading.hide();
      return;
    }

    const formData = getFormData(form);

    formData._token = $('meta[name="csrf-token"]').attr('content');

    try {
      await redaxios.put(window.route('password.resetPassword'), formData);
    } catch (error) {
      // noinspection ES6MissingAwait
      showSnackbar(Object.values(error.data.errors).join(' '), false);
      this.loading.hide();
      return;
    }

    Inertia.visit('/');

    // noinspection ES6MissingAwait
    showSnackbar(__('Reset della password effettuato con successo. Puoi ora accedere.'));
  }
}
