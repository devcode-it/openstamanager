import '@maicol07/mwc-card';
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

export default class AdminSetupPage extends Page {
  loading: Cash;

  view(vnode) {
    return (
      <mwc-card outlined className="center ext-container ext-container-small">
        <img src={logoUrl} className="center stretch" alt={__('OpenSTAManager')}/>
        <form id="new-admin" style="padding: 16px; text-align: center;">
          <h3 style="margin-top: 0;">{__('Creazione account amministratore')}</h3>
          <p>{__('Inserisci le informazioni richieste per creare un nuovo account amministratore.')}</p>
          <text-field label={__('Nome utente')} id="username" name="username" required style="margin-bottom: 16px;">
            <Mdi icon="account-outline" slot="icon"/>
          </text-field>
          <text-field label={__('Email')} id="email" name="email" type="email" required style="margin-bottom: 16px;">
            <Mdi icon="email-outline" slot="icon"/>
          </text-field>
          <text-field label={__('Password')} id="password" name="password" type="password" required style="margin-bottom: 16px;">
            <Mdi icon="lock-outline" slot="icon"/>
          </text-field>
          <text-field label={__('Conferma password')} id="password_confirm" name="password_confirm" type="password" required style="margin-bottom: 16px;">
            <Mdi icon="repeat-variant" slot="icon"/>
          </text-field>
          <LoadingButton raised id="create-account-button" label={__('Crea account')} icon="account-plus-outline" type="submit" />
        </form>
      </mwc-card>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.loading = $(this.element).find('#login-button mwc-circular-progress');

    $(this.element)
      .find('#create-account-button')
      .on('click', this.onCreateAccountButtonClicked.bind(this));
  }

  async onCreateAccountButtonClicked(event: PointerEvent) {
    event.preventDefault();
    this.loading.show();

    const form = $(this.element).find('form#new-admin');
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
      await redaxios.put(window.route('setup.admin.save'), formData);
    } catch (error) {
      showSnackbar(Object.values(error.data.errors).join(' '), false);
      this.loading.hide();
      return;
    }

    Inertia.visit('/');
    showSnackbar(__('Account creato con successo. Puoi ora accedere.'));
  }
}
