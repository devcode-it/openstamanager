import '@maicol07/mwc-card';
import '@maicol07/mwc-layout-grid';
import '@material/mwc-checkbox';
import '@material/mwc-formfield';
import '../WebComponents/TextField';

import type {Cash} from 'cash-dom';
import type {
  Vnode,
  VnodeDOM
} from 'mithril';
import redaxios from 'redaxios';

// eslint-disable-next-line import/no-absolute-path
import logoUrl from '/images/logo_completo.png';

import LoadingButton from '../Components/LoadingButton';
import Mdi from '../Components/Mdi';
import Page from '../Components/Page';
import {ErrorResponse} from '../types';
import {getFormData, isFormValid, showSnackbar} from '../utils';

export default class LoginPage extends Page {
  loading: Cash;
  forgotPasswordLoading: Cash;

  view(vnode: Vnode) {
    return (
      <mwc-card outlined className="center ext-container ext-container-small">
        <img
          src={logoUrl}
          className="center stretch"
          alt={__('OpenSTAManager')}
        />
        <form id="login" style="padding: 16px; text-align: center;">
          <h3 style="margin-top: 0;">{__('Accedi')}</h3>
          <text-field
            label={__('Nome utente/email')}
            id="username"
            name="username"
            required
            style="margin-bottom: 16px;"
          >
            <Mdi icon="account-outline" slot="icon" />
          </text-field>
          <text-field
            label={__('Password')}
            id="password"
            name="password"
            required
            type="password"
          >
            <Mdi icon="lock-outline" slot="icon" />
          </text-field>
          <mwc-formfield label={__('Ricordami')} style="display: block;">
            <mwc-checkbox id="remember" name="remember" />
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

  oncreate(vnode: VnodeDOM) {
    super.oncreate(vnode);
    this.loading = $(this.element).find('#login-button mwc-circular-progress');
    this.forgotPasswordLoading = $(this.element).find(
      '#forgot-password-button mwc-circular-progress'
    );
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
    formData._token = $('meta[name="csrf-token"]').attr('content') as string;

    try {
      await redaxios(route('auth.authenticate'), {
        method: 'POST',
        data: formData
      });
    } catch (error: any) {
      this.loading.hide();
      await showSnackbar(Object.values((error as ErrorResponse).data.errors).join(' '), false);
      return;
    }

    window.location.href = route('dashboard');
  }

  async onForgotPasswordButtonClicked() {
    this.forgotPasswordLoading.show();
    const field: HTMLInputElement | null = this.element.querySelector('#username');
    if (field) {
      field.type = 'email';

      if (!field.reportValidity()) {
        field.type = 'text';
        return;
      }

      field.type = 'text';

      try {
        await redaxios.post(route('password.forgot'), {
          email: field.value,
          _token: $('meta[name="csrf-token"]')
            .attr('content')
        });
      } catch (error: any) {
        this.loading.hide();
        await showSnackbar(Object.values((error as ErrorResponse).data.errors).join(' '), false);
        return;
      }

      this.loading.hide();
      await showSnackbar(__('La password è stata inviata alla tua email'));
    }
  }
}
