import '@maicol07/mwc-card';
import '@maicol07/mwc-layout-grid';
import '@material/mwc-checkbox';
import '@material/mwc-formfield';
import '../WebComponents/TextField';

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

export default class LoginPage extends Page {
  loading: Cash;
  forgotPasswordLoading: Cash;

  view(vnode) {
    return (
      <mwc-card outlined className="center ext-container ext-container-small">
        <img src={logoUrl} className="center stretch" alt={__('OpenSTAManager')}/>
        <form id="login" style="padding: 16px; text-align: center;">
          <h3 style="margin-top: 0;">{__('Accedi')}</h3>
          <text-field label={__('Nome utente/email')} id="username" name="username" required style="margin-bottom: 16px;">
            <Mdi icon="account-outline" slot="icon"/>
          </text-field>
          <text-field label={__('Password')} id="password" name="password" required type="password">
            <Mdi icon="lock-outline" slot="icon"/>
          </text-field>
          <mwc-formfield label={__('Ricordami')} style="display: block;">
            <mwc-checkbox id="remember" name="remember"/>
          </mwc-formfield>
          <LoadingButton
            type="submit"
            raised
            id="login-button"
            label={__('Accedi')}
            icon="login-variant"
            style="float: right;"
            onclick={this.onLoginButtonClicked.bind(this)}
          />
          <LoadingButton
            dense
            id="forgot-password-button"
            label="Password dimenticata"
            icon="lock-question"
            style="margin-top: 16px;"
            onclick={this.onForgotPasswordButtonClicked.bind(this)}
          />
        </form>
      </mwc-card>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.loading = $(this.element).find('#login-button mwc-circular-progress');
    this.forgotPasswordLoading = $(this.element).find('#forgot-password-button mwc-circular-progress');
  }

  async onLoginButtonClicked(event: PointerEvent) {
    event.preventDefault();
    this.loading.show();

    const form = $(this.element).find('#login');

    if (!isFormValid(form)) {
      this.loading.hide();
      return;
    }

    const formData = getFormData(form);

    formData._token = $('meta[name="csrf-token"]').attr('content');

    try {
      await redaxios(window.route('auth.authenticate'), {
        method: 'POST',
        data: formData
      });
    } catch (error) {
      // noinspection ES6MissingAwait
      showSnackbar(Object.values(error.data.errors).join(' '), false);
      this.loading.hide();
      return;
    }

    window.location.href = window.route('dashboard');
  }

  async onForgotPasswordButtonClicked() {
    this.forgotPasswordLoading.show();
    const field: HTMLFormElement = document.querySelector('#username');
    field.type = 'email';
    if (!field.reportValidity()) {
      field.type = 'text';
      return;
    }
    field.type = 'text';

    try {
      await redaxios.post(window.route('password.forgot'), {
        email: field.value,
        _token: $('meta[name="csrf-token"]').attr('content')
      });
    } catch (error) {
      // noinspection ES6MissingAwait
      showSnackbar(Object.values(error.data.errors).join(' '), false);
      this.loading.hide();
      return;
    }

    // noinspection ES6MissingAwait
    showSnackbar(__('La password è stata inviata alla tua email'));
    this.loading.hide();
  }
}
