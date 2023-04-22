import '@material/web/checkbox/checkbox.js';
import '@material/web/field/outlined-field.js';

import type {MdCheckbox} from '@material/web/checkbox/checkbox';
import {mdiLicense} from '@mdi/js';
import {
  Vnode,
  VnodeDOM
} from 'mithril';
import Stream from 'mithril/stream';

import MdIcon from '~/Components/MdIcon';
import {
  getFlag,
  getLocaleDisplayName
} from '~/utils/i18n';
import {
  capitalize,
  showSnackbar
} from '~/utils/misc';
import Request, {
  RequestError
} from '~/utils/Request';

import {
  SetupStep,
  SetupStepAttributes,
  SetupSteps
} from './SetupStep';

export interface WelcomeStepAttributes extends SetupStepAttributes {
  languages: string[];
  license: string;
}

export default class WelcomeStep extends SetupStep<WelcomeStepAttributes> {
  nextStep = SetupSteps.RegionalSettings;

  licenseAgreement = Stream(false);

  contents() {
    return (
      <div style={{textAlign: 'center'}}>
        <h3>{__('Benvenuto!')}</h3>
        <p>
          {__('Puoi procedere alla configurazione tecnica del software attraverso i seguenti parametri, che potranno essere corretti secondo necessità tramite il file  .env.')}
          <br/>
          {_v('Se necessiti supporto puoi contattarci tramite l\':contactLink o tramite il nostro :forumLink.', {
            contactLink: <a href="https://www.openstamanager.com/contattaci/?subject=Assistenza%20installazione%20OSM">{__('assistenza ufficiale')}</a>,
            forumLink: <a href="https://forum.openstamanager.com">{__('forum')}</a>
          })}
        </p>
        <div id="language-container"></div>
        {/* TODO: Wait https://github.com/material-components/material-web/issues/3933 */}
        {/* <md-filled-autocomplete id="language-select" name="locale" label={__('Lingua')}> */}
        {/*  /!*{SetupPage.languages(vnode)}*!/ */}
        {/* </md-filled-autocomplete> */}
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
          <md-text-button href="https://www.gnu.org/licenses/translations.en.html#GPL"
                          target="_blank" label={__('Versioni tradotte')}>
            <MdIcon icon={mdiLicense} slot="icon"/>
          </md-text-button>
        </div>
      </div>
    );
  }

  onLicenseAgreementChange(event: Event) {
    this.licenseAgreement((event.target as MdCheckbox).checked);
  }

  isNextButtonEnabled(vnode: Vnode<WelcomeStepAttributes, this>): boolean {
    return super.isNextButtonEnabled(vnode) && this.licenseAgreement();
  }

  oncreate(vnode: VnodeDOM<WelcomeStepAttributes, this>) {
    super.oncreate(vnode);

    // TODO: Workaround for https://github.com/material-components/material-web/issues/3933
    const container = this.element.querySelector<HTMLDivElement>('#language-container');
    if (container) {
      container.innerHTML = `
          <md-filled-autocomplete id="language-select" name="locale" label="${__('Lingua')}" value="${getLocaleDisplayName(app.locale) ?? ''}" readonly style="text-align: initial;">
            ${WelcomeStep.languages(vnode).join('')}
            ${getFlag(app.locale, 'leadingicon')}
          </md-filled-autocomplete>`;
      // const languageSelect = this.element.querySelector<Autocomplete>('#language-select');
      // languageSelect?.addEventListener('autocomplete-value-changed', (event: Event) => {
      //   WelcomeStep.onLanguageSelected(event as CustomEvent<{value: string}>);
      // });
    }
  }

  static languages(vnode: Vnode<WelcomeStepAttributes>) {
    const listItems = [];

    for (const lang of vnode.attrs.languages) {
      const language = lang;
      // TODO: Wait https://github.com/material-components/material-web/issues/3933
      // ListItems.push(
      //   <md-autocomplete-item graphic="icon" value={language} {...attributes}>
      //     {getFlag(langCode)}
      //     <span>{LocaleCode.getLanguageNativeName(langCode)}</span>
      //   </md-autocomplete-item>
      // );
      listItems.push(`
        <md-autocomplete-item headline="${capitalize(getLocaleDisplayName(language))}" data-value="${language}">
          ${getFlag(language)}
        </md-autocomplete-item>
      `);
    }

    return listItems;
  }

  // static async onLanguageSelected(event: CustomEvent<{value: string}>) {
  //   const {detail: {value}, target: autocomplete} = event;
  //   const field = autocomplete as Autocomplete;
  //   console.log(value, field);
  //
  //   const selectedItem = field.querySelector<AutocompleteItem>(`md-autocomplete-item[headline="${value}"]`);
  //   const selectedLangcode = selectedItem?.dataset.value;
  //
  //   if (selectedLangcode && selectedLangcode !== app.locale) {
  //     const selectedFlag = selectedItem?.querySelector('img');
  //     const fieldFlag: HTMLImageElement | null = field.querySelector<HTMLImageElement>(':scope > img[slot="leadingicon"]');
  //
  //     if (selectedFlag && fieldFlag) {
  //       fieldFlag.src = selectedFlag.src;
  //       fieldFlag.alt = selectedFlag.alt;
  //     }
  //
  //     try {
  //       const response = await Request.patch<{locale: string}>(route('app.language'), {locale: selectedLangcode});
  //       app.locale = response.locale;
  //     } catch (error: any) {
  //       await showSnackbar(__('Si è verificato un errore durante il salvataggio della lingua: :error', {error: (error as RequestError).message}));
  //     }
  //   }
  // }

  get data(): Record<string, any> {
    return {
      license_agreement: this.licenseAgreement()
    };
  }
}