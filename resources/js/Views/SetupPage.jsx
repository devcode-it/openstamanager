import '@maicol07/mwc-card';
import '@maicol07/mwc-layout-grid';
import '@material/mwc-button';
import '@material/mwc-checkbox';
import '@material/mwc-fab';
import '@material/mwc-formfield';
import '@material/mwc-list/mwc-list-item';
import '@material/mwc-select';
import '@material/mwc-textarea';
import '../WebComponents/TextField';

import collect from 'collect.js';
import LocaleCode from 'locale-code';
import Mithril from 'mithril';
import redaxios from 'redaxios';

// eslint-disable-next-line import/no-absolute-path
import logoUrl from '/images/logo_completo.png';

import {Alert} from '../Components';
import Mdi from '../Components/Mdi.jsx';
import Page from '../Components/Page.jsx';
import {
  getFormData,
  showSnackbar
} from '../utils';

export default class SetupPage extends Page {
  languages() {
    const listItems: Array[Mithril.Vnode] = [];

    for (const lang of this.page.props.languages) {
      const attributes = {
        selected: this.page.props.locale === lang
      };
      const langCode = lang.replace('_', '-');
      listItems.push(
        <mwc-list-item graphic="icon" value={lang} {...attributes}>
          <img
            slot="graphic"
            style="border-radius: 4px;"
            src={`https://flagicons.lipis.dev/flags/4x3/${LocaleCode.getCountryCode(langCode)
              .toLowerCase()}.svg`}
            alt={LocaleCode.getLanguageNativeName(langCode)}>
          </img>
          <span>{LocaleCode.getLanguageNativeName(langCode)}</span>
        </mwc-list-item>
      );
    }

    return listItems;
  }

  view(vnode) {
    const examplesTexts = collect();
    for (const example of ['localhost', 'root', 'mysql', 'openstamanager']) {
      examplesTexts.put(example, __('Esempio: :example', {example}));
    }

    return (
      <>
        <mwc-card outlined className="center" style="width: 95%;">
          <form id="setup">
            <img src={logoUrl} className="center" alt={__('OpenSTAManager')} />
            <mwc-layout-grid>
                <mwc-layout-grid-cell span-desktop="8">
                  <h2>{__('Benvenuto in :name!', {name: <strong>{__('OpenSTAManager')}</strong>})}</h2>
                  <p>{__('Puoi procedere alla configurazione tecnica del software attraverso i '
                    + 'parametri seguenti, che potranno essere corretti secondo necessità tramite il file .env.')}<br/>
                    {__("Se necessiti supporto puoi contattarci tramite l':contactLink o tramite il nostro :forumLink.", {
                      // eslint-disable-next-line no-secrets/no-secrets
                      contactLink: <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM">{__('assistenza ufficiale')}</a>,
                      forumLink: <a href="https://forum.openstamanager.com">{__('forum')}</a>
                    })}</p>
                  <h4>{__('Formato date')}</h4>
                  <p className="mdc-typography--subtitle2" style="font-size: small;">
                    {__('I formati sono impostabili attraverso lo standard previsto da :link.',
                      {link: <a href="https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters">PHP</a>})
                    }
                  </p>
                  <mwc-layout-grid inner>
                    <mwc-layout-grid-cell>
                      <text-field name="timestamp_format" label={__('Formato data lunga')}
                                  required value="d/m/Y H:i">
                        <Mdi icon="calendar-clock" slot="icon"/>
                      </text-field>
                    </mwc-layout-grid-cell>
                    <mwc-layout-grid-cell>
                      <text-field name="date_format" label={__('Formato data corta')}
                                  required value="d/m/Y">
                        <Mdi icon="calendar-month-outline" slot="icon"/>
                      </text-field>
                    </mwc-layout-grid-cell>
                    <mwc-layout-grid-cell>
                      <text-field name="time_format" label={__('Formato orario')} required
                                  value="H:i">
                        <Mdi icon="clock-outline" slot="icon"/>
                      </text-field>
                    </mwc-layout-grid-cell>
                  </mwc-layout-grid>
                  <hr/>
                  <h4>{__('Database')}</h4>
                  <mwc-layout-grid inner>
                    <mwc-layout-grid-cell span="4">
                      <text-field name="host" label={__('Host')} required
                                  helper={examplesTexts.get('localhost')}>
                        <Mdi icon="server-network" slot="icon"/>
                      </text-field>
                    </mwc-layout-grid-cell>
                    <mwc-layout-grid-cell span="4">
                      <text-field name="username" label={__('Nome utente')} required
                                  helper={examplesTexts.get('root')}>
                        <Mdi icon="account-outline" slot="icon"/>
                      </text-field>
                    </mwc-layout-grid-cell>
                    <mwc-layout-grid-cell span="4">
                      <text-field name="password" label={__('Password')}
                                  helper={examplesTexts.get('mysql')}>
                        <Mdi icon="lock-outline" slot="icon"/>
                      </text-field>
                    </mwc-layout-grid-cell>
                    <mwc-layout-grid-cell span="4">
                      <text-field name="database_name" label={__('Nome database')} required
                                  helper={examplesTexts.get('openstamanager')}>
                        <Mdi icon="database-outline" slot="icon"/>
                      </text-field>
                    </mwc-layout-grid-cell>
                  </mwc-layout-grid>
                  <hr/>
                  <mwc-layout-grid inner>
                    <mwc-layout-grid-cell>
                      <small>{__('* Campi obbligatori')}</small>
                    </mwc-layout-grid-cell>
                    <mwc-layout-grid-cell>
                      <mwc-button id="save-install" raised label={__('Salva e installa')} onclick={this.onSaveButtonClicked.bind(this)}>
                        <Mdi icon="check" slot="icon"/>
                      </mwc-button>
                    </mwc-layout-grid-cell>
                    <mwc-layout-grid-cell>
                      <mwc-button id="test-db" outlined label={__('Testa il database')} onclick={this.onTestButtonClicked.bind(this)}>
                        <Mdi icon="test-tube" slot="icon"/>
                      </mwc-button>
                    </mwc-layout-grid-cell>
                  </mwc-layout-grid>
                </mwc-layout-grid-cell>
                <mwc-layout-grid-cell>
                  <h4>{__('Lingua')}</h4>
                  <mwc-select id="language-select" name="locale">
                    {this.languages()}
                  </mwc-select>
                  <hr />
                  <h4>{__('Licenza')}</h4>
                  <p>{__('OpenSTAManager è tutelato dalla licenza GPL 3.0, da accettare obbligatoriamente per poter utilizzare il gestionale.')}</p>
                  <mwc-textarea value={this.page.props.license} rows="15" cols="40" disabled style="margin-bottom: 8px;"/>
                  <mwc-layout-grid inner>
                    <mwc-layout-grid-cell span-desktop="8" span-tablet="8">
                      <mwc-formfield label={__('Ho visionato e accetto la licenza')}>
                        <mwc-checkbox name="license_agreement"/>
                      </mwc-formfield>
                    </mwc-layout-grid-cell>
                    <mwc-layout-grid-cell>
                      <a href="https://www.gnu.org/licenses/translations.en.html#GPL" target="_blank">
                        <mwc-button label={__('Versioni tradotte')}>
                          <Mdi icon="license" slot="icon"/>
                        </mwc-button>
                      </a>
                    </mwc-layout-grid-cell>
                  </mwc-layout-grid>
                </mwc-layout-grid-cell>
            </mwc-layout-grid>
          </form>
        </mwc-card>
        <mwc-fab id="contrast-switcher" className="sticky contrast-light"
                 label={__('Attiva/disattiva contrasto elevato')}>
          <Mdi icon="contrast-circle" slot="icon" className="light-bg"/>
        </mwc-fab>
        <Alert id="test-connection-alert-error" icon="error"/>
        <Alert id="test-connection-alert-success" icon="success">
          <p>{__('Connessione al database riuscita')}</p>
        </Alert>
      </>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM) {
    super.oncreate(vnode);

    $('mwc-fab#contrast-switcher')
      .on('click', function () {
        $(this)
          .toggleClass('contrast-light')
          .toggleClass('contrast-dark');
        $('body')
          .toggleClass('mdc-high-contrast');
      });

    $('mwc-select#language-select').on('action', this.onLanguageSelected);

    // Fix for mwc button inside <a> tags
    $('a')
      .has('mwc-button')
      .css('text-decoration', 'none');
  }

  onTestButtonClicked(event: Event) {
    this.testDatabase();
  }

  onSaveButtonClicked(event: Event) {
    const form = $(event.target).closest('form');
    this.save(getFormData(form));
  }

  onLanguageSelected(event: Event) {
    window.location.href = window.route('app.language', {language: event.target.value});
  }

  async testDatabase(silentSuccess = false, silentError = false): boolean {
    const form = $('form');

    try {
      await redaxios.options(window.route('setup.test'), {data: getFormData(form)});
    } catch (error) {
      if (!silentError) {
        const alert = $('#test-connection-alert-error');
        alert.find('.content').text(__('Si è verificato un errore durante la connessione al'
          + ' database: :error', {error: error.data.error}));
        alert.get(0).show();
      }
      return false;
    }

    if (!silentSuccess) {
      document.querySelector('#test-connection-alert-success')
        .show();
    }
    return true;
  }

  async save(data: {...}) {
    const test = this.testDatabase(true);
    if (!test) {
      return;
    }

    try {
      await redaxios.put(window.route('setup.save'), data);
    } catch (error) {
      await showSnackbar(error.data.error_description);
      return;
    }

    await showSnackbar(__('Impostazioni salvate correttamente'));
    window.location.href = window.route('auth.login');
  }
}
