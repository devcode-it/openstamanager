import '@maicol07/mwc-card';
import '@maicol07/mwc-layout-grid';
import '@material/mwc-button';
import '@material/mwc-checkbox';
import '@material/mwc-fab';
import '@material/mwc-formfield';
import '@material/mwc-list/mwc-list-item';
import '@material/mwc-select';
import '../WebComponents/TextArea';
import '../WebComponents/TextField';
import '../WebComponents/Select';

import type {Dialog as MWCDialog} from '@material/mwc-dialog';
import type CSS from 'csstype';
import LocaleCode from 'locale-code';
import type {Vnode, VnodeDOM} from 'mithril';
import redaxios, {Response} from 'redaxios';

// @ts-ignore
// eslint-disable-next-line import/no-absolute-path
import logoUrl from '/images/logo_completo.png';

import {Alert} from '../Components';
import Mdi from '../Components/Mdi';
import Page from '../Components/Page';
import {getFormData, showSnackbar} from '../utils';

function getFlag(language: string, slot: string = 'graphic', styles: CSS.Properties = {}) {
  if (!styles.display) {
    styles.display = 'flex';
  }

  return (
    <div slot={slot} style={styles}>
      <img
        style="border-radius: 4px;"
        src={`https://flagicons.lipis.dev/flags/4x3/${LocaleCode.getCountryCode(
          language
        ).toLowerCase()}.svg`}
        alt={LocaleCode.getLanguageNativeName(language)}
      />
    </div>
  );
}

export default class SetupPage extends Page {
  languages() {
    const listItems: Vnode[] = [];

    for (const lang of this.page.props.languages) {
      const language = lang as string;
      const attributes = {
        selected: this.page.props.locale === lang
      };
      const langCode = language.replace('_', '-');
      listItems.push(
        <mwc-list-item graphic="icon" value={language} {...attributes}>
          {getFlag(langCode)}
          <span>{LocaleCode.getLanguageNativeName(langCode)}</span>
        </mwc-list-item>
      );

      if (attributes.selected) {
        listItems.push(
          getFlag(langCode, 'icon', {
            display: 'block',
            width: '24px',
            lineHeight: '22px'
          })
        );
      }
    }

    return listItems;
  }

  view() {
    const examplesTexts: Record<string, string> = {};

    for (const example of ['localhost', 'root', 'mysql', 'openstamanager']) {
      examplesTexts[example] = __('Esempio: :example', {
        example
      });
    }

    return (
      <>
        <mwc-card outlined className="center ext-container">
          <form id="setup">
            <img src={logoUrl as string} className="center" alt={__('OpenSTAManager')} />
            <mwc-layout-grid>
              <mwc-layout-grid-cell span-desktop={8}>
                <h2>
                  {__('Benvenuto in :name!', {
                    name: <strong>{__('OpenSTAManager')}</strong>
                  })}
                </h2>
                <p>
                  {__(
                    'Puoi procedere alla configurazione tecnica del software attraverso i '
                      + 'parametri seguenti, che potranno essere corretti secondo necessità tramite il file .env.'
                  )}
                  <br />
                  {__(
                    "Se necessiti supporto puoi contattarci tramite l':contactLink o tramite il nostro :forumLink.",
                    {
                      contactLink: (
                        <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM">
                          {__('assistenza ufficiale')}
                        </a>
                      ),
                      forumLink: (
                        <a href="https://forum.openstamanager.com">
                          {__('forum')}
                        </a>
                      )
                    }
                  )}
                </p>
                <h4>{__('Formato date')}</h4>
                <p
                  className="mdc-typography--subtitle2"
                  style="font-size: small;"
                >
                  {__(
                    'I formati sono impostabili attraverso lo standard previsto da :link.',
                    {
                      link: (
                        <a href="https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters">
                          PHP
                        </a>
                      )
                    }
                  )}
                </p>
                <mwc-layout-grid inner>
                  <mwc-layout-grid-cell>
                    <text-field
                      name="timestamp_format"
                      label={__('Formato data lunga')}
                      required
                      value="d/m/Y H:i"
                    >
                      <Mdi icon="calendar-clock" slot="icon" />
                    </text-field>
                  </mwc-layout-grid-cell>
                  <mwc-layout-grid-cell>
                    <text-field
                      name="date_format"
                      label={__('Formato data corta')}
                      required
                      value="d/m/Y"
                    >
                      <Mdi icon="calendar-month-outline" slot="icon" />
                    </text-field>
                  </mwc-layout-grid-cell>
                  <mwc-layout-grid-cell>
                    <text-field
                      name="time_format"
                      label={__('Formato orario')}
                      required
                      value="H:i"
                    >
                      <Mdi icon="clock-outline" slot="icon" />
                    </text-field>
                  </mwc-layout-grid-cell>
                </mwc-layout-grid>
                <hr />
                <h4>{__('Database')}</h4>
                <mwc-layout-grid inner>
                  <mwc-layout-grid-cell span="4">
                    <text-field
                      name="host"
                      label={__('Host')}
                      required
                      helper={examplesTexts.localhost}
                    >
                      <Mdi icon="server-network" slot="icon" />
                    </text-field>
                  </mwc-layout-grid-cell>
                  <mwc-layout-grid-cell span="4">
                    <text-field
                      name="username"
                      label={__('Nome utente')}
                      required
                      helper={examplesTexts.root}
                    >
                      <Mdi icon="account-outline" slot="icon" />
                    </text-field>
                  </mwc-layout-grid-cell>
                  <mwc-layout-grid-cell span="4">
                    <text-field
                      name="password"
                      label={__('Password')}
                      helper={examplesTexts.mysql}
                    >
                      <Mdi icon="lock-outline" slot="icon" />
                    </text-field>
                  </mwc-layout-grid-cell>
                  <mwc-layout-grid-cell span="4">
                    <text-field
                      name="database_name"
                      label={__('Nome database')}
                      required
                      helper={examplesTexts.openstamanager}
                    >
                      <Mdi icon="database-outline" slot="icon" />
                    </text-field>
                  </mwc-layout-grid-cell>
                </mwc-layout-grid>
                <hr />
                <mwc-layout-grid inner>
                  <mwc-layout-grid-cell>
                    <small>{__('* Campi obbligatori')}</small>
                  </mwc-layout-grid-cell>
                  <mwc-layout-grid-cell>
                    <mwc-button
                      id="save-install"
                      raised
                      label={__('Salva e installa')}
                      onclick={this.onSaveButtonClicked.bind(this)}
                    >
                      <Mdi icon="check" slot="icon" />
                    </mwc-button>
                  </mwc-layout-grid-cell>
                  <mwc-layout-grid-cell>
                    <mwc-button
                      id="test-db"
                      outlined
                      label={__('Testa il database')}
                      onclick={this.onTestButtonClicked.bind(this)}
                    >
                      <Mdi icon="test-tube" slot="icon" />
                    </mwc-button>
                  </mwc-layout-grid-cell>
                </mwc-layout-grid>
              </mwc-layout-grid-cell>
              <mwc-layout-grid-cell>
                <h4>{__('Lingua')}</h4>
                <material-select id="language-select" name="locale">
                  {this.languages()}
                </material-select>
                <hr />
                <h4>{__('Licenza')}</h4>
                <p>
                  {__(
                    'OpenSTAManager è tutelato dalla licenza GPL 3.0, da accettare obbligatoriamente per poter utilizzare il gestionale.'
                  )}
                </p>
                <text-area
                  value={this.page.props.license as string}
                  rows={15}
                  cols={40}
                  disabled
                  style="margin-bottom: 8px;"
                />
                <mwc-layout-grid inner>
                  <mwc-layout-grid-cell span-desktop={8} span-tablet={8}>
                    <mwc-formfield
                      label={__('Ho visionato e accetto la licenza')}
                    >
                      <mwc-checkbox name="license_agreement" />
                    </mwc-formfield>
                  </mwc-layout-grid-cell>
                  <mwc-layout-grid-cell>
                    <a
                      href="https://www.gnu.org/licenses/translations.en.html#GPL"
                      target="_blank"
                    >
                      <mwc-button label={__('Versioni tradotte')}>
                        <Mdi icon="license" slot="icon" />
                      </mwc-button>
                    </a>
                  </mwc-layout-grid-cell>
                </mwc-layout-grid>
              </mwc-layout-grid-cell>
            </mwc-layout-grid>
          </form>
        </mwc-card>
        <mwc-fab
          id="contrast-switcher"
          className="sticky contrast-light"
          label={__('Attiva/disattiva contrasto elevato')}
        >
          <Mdi icon="contrast-circle" slot="icon" className="light-bg" />
        </mwc-fab>
        <Alert id="test-connection-alert-error" icon="error" />
        <Alert id="test-connection-alert-success" icon="success">
          <p>{__('Connessione al database riuscita')}</p>
        </Alert>
      </>
    );
  }

  oncreate(vnode: VnodeDOM) {
    super.oncreate(vnode);
    $('mwc-fab#contrast-switcher').on('click', function (this: HTMLElement) {
      $(this).toggleClass('contrast-light').toggleClass('contrast-dark');
      $('body').toggleClass('mdc-high-contrast');
    });
    $('#language-select').on('action', (event: Event) => this.onLanguageSelected(event as Event & {detail: {index: number}}));
  }

  async onTestButtonClicked() {
    await this.testDatabase();
  }

  async onSaveButtonClicked(event: Event) {
    const form = $(event.target as HTMLElement)
      .closest('form');
    await this.save(getFormData(form));
  }

  onLanguageSelected(event: Event & {detail: {index: number}}) {
    const {detail, target: select} = event;
    const selected: HTMLImageElement | null = (select as HTMLElement).querySelector(
      `mwc-list-item:nth-child(${detail.index + 1}) [slot="graphic"] img`
    );
    const selectIcon: HTMLImageElement | null = (select as HTMLElement).querySelector('[slot="icon"] img');

    if (selected && selectIcon) {
      selectIcon.src = selected.src;
    }

    window.location.href = route('app.language', {
      language: (select as HTMLInputElement).value
    });
  }

  async testDatabase(silentSuccess = false, silentError = false): Promise<boolean> {
    const form = $('form');

    try {
      await redaxios.options(route('setup.test'), {
        data: getFormData(form)
      });
    } catch (error: any) {
      if (!silentError) {
        const alert = this.element.querySelector('#test-connection-alert-error');
        if (alert) {
          const content = alert.querySelector('.content');

          if (content) {
            content.textContent = __(
              'Si è verificato un errore durante la connessione al'
              + ' database: :error',
              {
                error: (error as Response<{error: string}>).data.error
              }
            );
          }

          (alert as MWCDialog).show();
        }
      }

      return false;
    }

    if (!silentSuccess) {
      const alert = document.querySelector('#test-connection-alert-success');
      if (alert) {
        (alert as MWCDialog).show();
      }
    }

    return true;
  }

  async save(data: {}) {
    const test = await this.testDatabase(true);

    if (!test) {
      return;
    }

    try {
      await redaxios.put(route('setup.save'), data);
    } catch (error: any) {
      await showSnackbar((error as Response<{error_description: string}>).data.error_description);
      return;
    }

    await showSnackbar(__('Impostazioni salvate correttamente'));
    window.location.href = route('setup.admin');
  }
}
