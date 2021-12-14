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
          <mwc-button dense label="Password dimenticata" style="margin-top: 16px;">
            <Mdi icon="lock-question" slot="icon"/>
          </mwc-button>
        </form>
      </mwc-card>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.loading = $(this.element).find('#login-button mwc-circular-progress');
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
      showSnackbar(Object.values(error.data.errors).join(' '), false);
      this.loading.hide();
      return;
    }

    window.location.href = window.route('dashboard');
  }
}
