// noinspection DuplicatedCode
import '@maicol07/mwc-card';
import '@maicol07/mwc-layout-grid';
import '../WebComponents/TextField';

import {Inertia} from '@inertiajs/inertia';
import {
  LoadingButton,
  Mdi,
  Page,
  PageAttributes
} from '@osm/Components';
import logoUrl from '@osm-images/logo_completo.png';
import type {Cash} from 'cash-dom';
import type {
  Vnode,
  VnodeDOM
} from 'mithril';
import redaxios from 'redaxios';

import {ErrorResponse} from '../typings';
import {
  getFormData,
  isFormValid,
  showSnackbar,
  validatePassword
} from '../utils';

export class ResetPasswordPage extends Page {
  loading: Cash;
  parameters: URLSearchParams;

  oninit(vnode: Vnode<PageAttributes>) {
    super.oninit(vnode);
    this.parameters = new URLSearchParams(window.location.search);
  }

  view() {
    return (
      <mwc-card outlined className="center ext-container ext-container-small">
        <img
          src={logoUrl}
          className="center stretch"
          alt={__('OpenSTAManager')}
        />
        <form id="reset-password" style="padding: 16px; text-align: center;">
          <h3 style="margin-top: 0;">{__('Reimposta password')}</h3>
          <input
            hidden
            id="email"
            name="email"
            value={this.parameters.get('email')}
          />
          <input
            hidden
            id="token"
            name="token"
            value={this.parameters.get('token')}
          />
          <text-field
            label={__('Password')}
            id="password"
            name="password"
            required
            type="password"
          >
            <Mdi icon="lock-outline" slot="icon"/>
          </text-field>
          <text-field
            label={__('Conferma password')}
            id="password_confirm"
            name="password_confirm"
            type="password"
            required
            style="margin-top: 16px;"
          >
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

  oncreate(vnode: VnodeDOM<PageAttributes>) {
    super.oncreate(vnode);
    this.loading = $(this.element)
      .find('#reset-password mwc-circular-progress');
  }

  async onResetPasswordButtonClicked(event: MouseEvent) {
    event.preventDefault();
    this.loading.show();
    const form = $(this.element)
      .find('#reset-password');

    // noinspection DuplicatedCode
    const password: HTMLElement | undefined = form.find('#password')
      .get(0);
    const passwordConfirm: HTMLElement | undefined = form.find('#password_confirm')
      .get(0);

    validatePassword(password as HTMLInputElement, passwordConfirm as HTMLInputElement);

    if (!isFormValid(form)) {
      this.loading.hide();
      return;
    }

    const formData = getFormData(form);
    formData._token = $('meta[name="csrf-token"]')
      .attr('content') as string;

    try {
      await redaxios.put(route('password.resetPassword'), formData);
    } catch (error: any) {
      this.loading.hide();
      await showSnackbar(Object.values((error as ErrorResponse).data.errors)
        .join(' '), false);
      return;
    }

    Inertia.visit('/');
    await showSnackbar(
      __('Reset della password effettuato con successo. Puoi ora accedere.')
    );
  }
}
