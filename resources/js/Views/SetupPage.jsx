import '@material/mwc-button';
import '@material/mwc-checkbox';
import '@material/mwc-fab';
import '@material/mwc-formfield';
import '@material/mwc-list/mwc-list-item';
import '@material/mwc-select';
import '@material/mwc-textarea';
import '@material/mwc-textfield';

import collect from 'collect.js';
import LocaleCode from 'locale-code';
import Mithril from 'mithril';

import logoUrl from '@/static/images/logo_completo.png';

import Card from '../Components/Card/Card';
import Content from '../Components/Card/Content';
import Cell from '../Components/Grid/Cell';
import LayoutGrid from '../Components/Grid/LayoutGrid';
import Row from '../Components/Grid/Row';
import Mdi from '../Components/Mdi';
import Page from '../Components/Page';

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
            src={`https://lipis.github.io/flag-icon-css/flags/4x3/${LocaleCode.getCountryCode(langCode)
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
      examplesTexts.put(example, this.__('Esempio: :example', {example}));
    }

    return (
      <>
        <Card outlined className="center" style="width: 85%;">
          <Content>
            <img src={logoUrl} className="center" alt={this.__('OpenSTAManager')} />
            <LayoutGrid>
              <Row>
                <Cell columnspan-desktop="8">
                  <h2>{this.__('Benvenuto in :name!', {name: <strong>{this.__('OpenSTAManager')}</strong>})}</h2>
                  <p>{this.__('Puoi procedere alla configurazione tecnica del software attraverso i '
                    + 'parametri seguenti, che potranno essere corretti secondo necessità tramite il file .env.')}<br/>
                    {this.__("Se necessiti supporto puoi contattarci tramite l':contactLink o tramite il nostro :forumLink.", {
                      // eslint-disable-next-line no-secrets/no-secrets
                      contactLink: <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM">{this.__('assistenza ufficiale')}</a>,
                      forumLink: <a href="https://forum.openstamanager.com">{this.__('forum')}</a>
                    })}</p>
                  <h4>{this.__('Formato date')}</h4>
                  <small>
                    {this.__('I formati sono impostabili attraverso lo standard previsto da :link.',
                      {link: <a href="https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters">PHP</a>})
                    }
                  </small>
                  <Row style="margin-top: 8px;">
                    <Cell>
                      <mwc-textfield name="timestamp_format" label={this.__('Formato data lunga')}
                                     required value="d/m/Y H:i"/>
                    </Cell>
                    <Cell>
                      <mwc-textfield name="date_format" label={this.__('Formato data corta')}
                                     required value="d/m/Y"/>
                    </Cell>
                    <Cell>
                      <mwc-textfield name="time_format" label={this.__('Formato orario')} required
                                     value="H:i"/>
                    </Cell>
                  </Row>
                  <hr/>
                  <h4>{this.__('Database')}</h4>
                  <Row>
                    <Cell columnspan="4">
                      <mwc-textfield name="host" label={this.__('Host')} required
                                     helper={examplesTexts.get('localhost')}/>
                    </Cell>
                    <Cell columnspan="4">
                      <mwc-textfield name="username" label={this.__('Nome utente')} required
                                     helper={examplesTexts.get('root')}/>
                    </Cell>
                    <Cell columnspan="4">
                      <mwc-textfield name="password" label={this.__('Password')} required
                                     helper={examplesTexts.get('mysql')}/>
                    </Cell>
                    <Cell columnspan="4">
                      <mwc-textfield name="database_name" label={this.__('Nome database')} required
                                     helper={examplesTexts.get('openstamanager')}/>
                    </Cell>
                  </Row>
                  <hr/>
                  <Row>
                    <Cell>
                      <small>{this.__('* Campi obbligatori')}</small>
                    </Cell>
                    <Cell>
                      <mwc-button raised label={this.__('Salva e installa')}>
                        <Mdi icon="check" slot="icon"/>
                      </mwc-button>
                    </Cell>
                    <Cell>
                      <mwc-button outlined label={this.__('Testa il database')}>
                        <Mdi icon="test-tube" slot="icon"/>
                      </mwc-button>
                    </Cell>
                  </Row>
                </Cell>
                <Cell>
                  <h4>{this.__('Lingua')}</h4>
                  <mwc-select>
                    {this.languages()}
                  </mwc-select>
                  <hr />
                  <h4>{this.__('Licenza')}</h4>
                  <p>{this.__('OpenSTAManager è tutelato dalla licenza GPL 3.0, da accettare obbligatoriamente per poter utilizzare il gestionale.')}</p>
                  <mwc-textarea value={this.page.props.license} rows="15" cols="40" disabled />
                  <Row style="margin-top: 5px;">
                    <Cell columnspan-desktop="8" columnspan-tablet="8">
                      <mwc-formfield label={this.__('Ho visionato e accetto la licenza')}>
                        <mwc-checkbox name="license_agreement"/>
                      </mwc-formfield>
                    </Cell>
                    <Cell>
                      <a href="https://www.gnu.org/licenses/translations.en.html#GPL" target="_blank">
                        <mwc-button label={this.__('Versioni tradotte')}>
                          <Mdi icon="license" slot="icon"/>
                        </mwc-button>
                      </a>
                    </Cell>
                  </Row>
                </Cell>
              </Row>
            </LayoutGrid>
          </Content>
        </Card>
        <mwc-fab id="contrast-switcher" className="sticky contrast-light"
                 label={this.__('Attiva/disattiva contrasto elevato')}>
          <Mdi icon="contrast-circle" slot="icon" className="light-bg"/>
        </mwc-fab>
      </>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM) {
    super.oncreate(vnode);

    $('mwc-fab#contrast-switcher').on('click', function () {
      $(this).toggleClass('contrast-light').toggleClass('contrast-dark');
      $('body').toggleClass('mdc-high-contrast');
    });
  }
}
