import '@material/mwc-button';
import '@material/mwc-checkbox';
import '@material/mwc-fab';
import '@material/mwc-formfield';
import '@material/mwc-list/mwc-list-item';
import '@material/mwc-select';
import '@material/mwc-textarea';
import '@material/mwc-textfield';
import LocaleCode from 'locale-code';
import Mithril from 'mithril';

import Page from '../Components/Page';
import LayoutGrid from '../Components/Grid/LayoutGrid';
import Row from '../Components/Grid/Row';
import Cell from '../Components/Grid/Cell';
import Mdi from '../Components/Mdi';
import Card from '../Components/Card/Card';
import Content from '../Components/Card/Content';

export default class SetupPage extends Page {
  // eslint-disable-next-line no-unused-vars
  view(vnode) {
    const listItems: array[Mithril.Vnode] = [];

    // noinspection JSUnresolvedVariable
    this.page.props.languages.forEach((lang) => {
      const prop = {
        selected: this.page.props.locale === lang
      };
      const langCode = lang.replace('_', '-');
      listItems.push(
        <mwc-list-item graphic="icon" value={lang} {...prop}>
          <img
            slot="graphic"
            style="border-radius: 4px;"
            src={`https://lipis.github.io/flag-icon-css/flags/4x3/${LocaleCode.getCountryCode(langCode).toLowerCase()}.svg`}
            alt={LocaleCode.getLanguageNativeName(langCode)}>
          </img>
        <span>{LocaleCode.getLanguageNativeName(langCode)}</span>
      </mwc-list-item>
      );
    });

    return (
      <>
        <Card outlined class="center" style="width: 85%;">
          <Content>
            <img src="images/logo_completo.png" class="center" alt={this.__('OpenSTAManager')} />
            <LayoutGrid>
              <Row>
                <Cell columnspan-desktop="8">
                  <h2>{this.__('Benvenuto in :name!', {name: <strong>{this.__('OpenSTAManager')}</strong>})}</h2>
                  <p>{this.__('Puoi procedere alla configurazione tecnica del software attraverso i '
                    + 'parametri seguenti, che potranno essere corretti secondo necessità tramite il file .env.')}<br/>
                    {this.__("Se necessiti supporto puoi contattarci tramite l':contact_link o tramite il nostro :forum_link.", {
                      contact_link: <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM">{this.__('assistenza ufficiale')}</a>,
                      forum_link: <a href="https://forum.openstamanager.com">{this.__('forum')}</a>
                    })}</p>
                  <h4>{this.__('Formato date')}</h4>
                  <small>
                    {this.__('I formati sono impostabili attraverso lo standard previsto da :link.',
                      {link: <a href="https://www.php.net/manual/en/function.date.php#refsect1-function.date-parameters">PHP</a>})
                    }
                  </small>
                  <Row style="margin-top: 8px;">
                    <Cell>
                      <mwc-textfield name="timestamp_format" label={this.__('Formato data lunga', true)} required value="d/m/Y H:i"/>
                    </Cell>
                    <Cell>
                      <mwc-textfield name="date_format" label={this.__('Formato data corta', true)} required value="d/m/Y"/>
                    </Cell>
                    <Cell>
                      <mwc-textfield name="time_format" label={this.__('Formato orario', true)} required value="H:i"/>
                    </Cell>
                  </Row>
                  <hr/>
                  <h4>{this.__('Database')}</h4>
                  <Row>
                    <Cell columnspan="4">
                      <mwc-textfield name="host" label={this.__('Host', true)} required helper={this.__('Esempio: :example', {example: 'localhost'}, true)} />
                    </Cell>
                    <Cell columnspan="4">
                      <mwc-textfield name="username" label={this.__('Nome utente', true)} required helper={this.__('Esempio: :example', {example: 'root'}, true)} />
                    </Cell>
                    <Cell columnspan="4">
                      <mwc-textfield name="password" label={this.__('Password', true)} required helper={this.__('Esempio: :example', {example: 'mysql'}, true)} />
                    </Cell>
                    <Cell columnspan="4">
                      <mwc-textfield name="database_name" label={this.__('Nome database', true)} required helper={this.__('Esempio: :example', {example: 'openstamanager'}, true)} />
                    </Cell>
                  </Row>
                  <hr/>
                  <Row>
                    <Cell>
                      <small>{this.__('* Campi obbligatori')}</small>
                    </Cell>
                    <Cell>
                      <mwc-button raised label={this.__('Salva e installa', true)}>
                        <Mdi icon="check" slot="icon" />
                      </mwc-button>
                    </Cell>
                    <Cell>
                      <mwc-button outlined label={this.__('Testa il database', true)}>
                        <Mdi icon="test-tube" slot="icon" />
                      </mwc-button>
                    </Cell>
                  </Row>
                </Cell>
                <Cell>
                  <h4>{this.__('Lingua')}</h4>
                  <mwc-select>
                    {listItems}
                  </mwc-select>
                  <hr />
                  <h4>{this.__('Licenza')}</h4>
                  <p>{this.__('OpenSTAManager è tutelato dalla licenza GPL 3.0, da accettare obbligatoriamente per poter utilizzare il gestionale.')}</p>
                  <mwc-textarea value={this.page.props.license} rows="15" cols="40" disabled />
                  <Row style="margin-top: 5px;">
                    <Cell columnspan-desktop="8" columnspan-tablet="8">
                      <mwc-formfield label={this.__('Ho visionato e accetto la licenza', true)}>
                        <mwc-checkbox name="license_agreement" />
                      </mwc-formfield>
                    </Cell>
                    <Cell>
                      <a href="https://www.gnu.org/licenses/translations.en.html#GPL" target="_blank">
                        <mwc-button label={this.__('Versioni tradotte', true)}>
                          <Mdi icon="license" slot="icon" />
                        </mwc-button>
                      </a>
                    </Cell>
                  </Row>
                </Cell>
              </Row>
            </LayoutGrid>
          </Content>
        </Card>
        <mwc-fab id="contrast-switcher" class="sticky contrast-light" label={this.__('Attiva/disattiva contrasto elevato', true)}>
          <Mdi icon="contrast-circle" slot="icon" class="light-bg"/>
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
