import '@maicol07/mwc-card';
import '@material/mwc-checkbox';
import '@material/mwc-formfield';
import '@maicol07/mwc-layout-grid';
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
          <text-field label={__('Nome utente/email')} id="username" name="username" style="margin-bottom: 16px;">
            <Mdi icon="account-outline" slot="icon"/>
          </text-field>
          <text-field label={__('Password')} id="password" name="password" type="password">
            <Mdi icon="lock-outline" slot="icon"/>
          </text-field>
          <mwc-formfield label={__('Ricordami')} style="display: block;">
            <mwc-checkbox id="remember" name="remember"/>
          </mwc-formfield>
          <LoadingButton raised id="login-button" label={__('Accedi')} icon="login-variant" style="float: right;"/>
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

    $(this.element)
      .find('#login-button')
      .on('click', this.onLoginButtonClicked.bind(this));
  }

  async onLoginButtonClicked() {
    this.loading.show();

    const formData = getFormData($(this.element)
      .find('#login'));

    formData._token = $('meta[name="csrf-token"]').attr('content');

    try {
      await redaxios(window.route('auth.authenticate'), {
        method: 'POST',
        data: formData
      });
    } catch (error) {
      showSnackbar(Object.values(error.data.errors).join(' '), false);
      this.loading.hide();
    }

    // Inertia.visit(window.route('dashboard'));
  }
}
