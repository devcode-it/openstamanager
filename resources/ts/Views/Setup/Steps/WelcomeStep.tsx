import '@material/web/checkbox/checkbox.js';
import '@material/web/field/outlined-field.js';
import '@material/web/select/filled-select.js';
import '@material/web/select/select-option.js';

import type {MdCheckbox} from '@material/web/checkbox/checkbox';
import {Select} from '@material/web/select/lib/select';
import {mdiLicense} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import {
  getFlag,
  getLocaleDisplayName
} from '@osm/utils/i18n';
import {
  capitalize,
  showSnackbar
} from '@osm/utils/misc';
import {Vnode} from 'mithril';
import Stream from 'mithril/stream';
import {
  Request,
  RequestError
} from 'mithril-utilities';

import {
  SetupStep,
  SetupStepAttributes,
  SetupSteps
} from './SetupStep';

export interface WelcomeStepAttributes extends SetupStepAttributes {
  languages: string[];
  license: string;
}

export default class WelcomeStep<A extends WelcomeStepAttributes = WelcomeStepAttributes> extends SetupStep<A> {
  nextStep = SetupSteps.RegionalSettings;

  licenseAgreement = Stream(false);

  contents(vnode: Vnode<A, this>) {
    return (
      <div style={{textAlign: 'center'}}>
        <h3>{__('Benvenuto!')}</h3>
        <p>
          {__('Puoi procedere alla configurazione tecnica del software attraverso i seguenti parametri, che potranno essere corretti secondo necessità tramite il file .env.')}
          <br/>
          {_v('Se necessiti supporto puoi contattarci tramite l\':contactLink o tramite il nostro :forumLink.', {
            contactLink: <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM">{__('assistenza ufficiale')}</a>,
            forumLink: <a href="https://forum.openstamanager.com">{__('forum')}</a>
          })}
        </p>
        <md-filled-select id="language-select" name="locale" label={__('Lingua')} required value={app.locale} style={{textAlign: 'initial'}} oninput={WelcomeStep.onLanguageSelected}>
          {getFlag(app.locale, 'leadingicon')}
          {vnode.attrs.languages.map((locale) => (
            <md-select-option key={locale} value={locale} headline={capitalize(getLocaleDisplayName(locale))}>
              {getFlag(locale, 'start', {marginLeft: '16px'})}
            </md-select-option>
          ))}
        </md-filled-select>
        <h4>{__('Licenza')}</h4>
        <p>{__('OpenSTAManager è tutelato dalla licenza GPL 3.0, da accettare obbligatoriamente per poter utilizzare il gestionale.')}</p>
        <md-outlined-field populated style={{marginBottom: '8px'}}>
          <p style={{
            overflow: 'auto', resize: 'both', height: '250px', fontFamily: 'monospace'
          }}>{this.attrs.get('license')}</p>
        </md-outlined-field>
        <div style={{display: 'flex', alignItems: 'center', justifyContent: 'space-between'}}>
          <label style={{display: 'flex', alignItems: 'center'}}>
            <md-checkbox name="license_agreement" ariaRequired="true" checked={this.licenseAgreement()} onchange={this.onLicenseAgreementChange.bind(this)}/>
            {__('Ho visionato e accetto la licenza')}
          </label>
          <md-text-button href="https://www.gnu.org/licenses/translations.en.html#GPL" target="_blank">
            {__('Versioni tradotte')}
            <MdIcon icon={mdiLicense} slot="icon"/>
          </md-text-button>
        </div>
      </div>
    );
  }

  onLicenseAgreementChange(event: Event) {
    this.licenseAgreement((event.target as MdCheckbox).checked);
  }

  isNextButtonEnabled(vnode: Vnode<A, this>): boolean {
    return super.isNextButtonEnabled(vnode) && this.licenseAgreement();
  }

  static async onLanguageSelected(this: void, event: Event) {
    const select = event.target as Select;
    const locale = select.value;

    if (locale && locale !== app.locale) {
      try {
        const response = await Request.patch<{locale: string}>(route('app.language'), {locale});
        app.locale = response.locale;
      } catch (error: any) {
        void showSnackbar(__('Si è verificato un errore durante il salvataggio della lingua: :error', {error: (error as RequestError<{message: string}>).response.message}));
      }
      m.redraw();
    }
  }

  get data(): Record<string, any> {
    return {
      license_agreement: this.licenseAgreement()
    };
  }
}
