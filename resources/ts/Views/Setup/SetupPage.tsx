import {router} from '@maicol07/inertia-mithril';
import '@maicol07/material-web-additions/card/elevated-card.js';
import type {Vnode} from 'mithril';
import {
  Request,
  RequestError
} from 'mithril-utilities';
import Stream from 'mithril/stream';

import Page, {PageAttributes} from '~/Components/Page';
import {showSnackbar} from '~/utils/misc';
import AdminUserStep from '~/Views/Setup/Steps/AdminUserStep';
import DatabaseStep from '~/Views/Setup/Steps/DatabaseStep';
import RegionalSettings from '~/Views/Setup/Steps/RegionalSettings';

import {
  SetupStep,
  SetupSteps
} from './Steps/SetupStep';
import WelcomeStep from './Steps/WelcomeStep';

export interface SetupPageAttributes extends PageAttributes<{
  languages: string[];
  license: string;
}> {
}

export default class SetupPage extends Page<SetupPageAttributes> {
  currentStep = Stream<SetupSteps>(SetupSteps.Welcome);
  steps: Record<SetupSteps, SetupStep<any>> = {
    [SetupSteps.Welcome]: new WelcomeStep(),
    [SetupSteps.RegionalSettings]: new RegionalSettings(),
    [SetupSteps.Database]: new DatabaseStep(),
    [SetupSteps.AdminUser]: new AdminUserStep()
  };

  contents(vnode: Vnode<SetupPageAttributes>) {
    return <>
      <h1>{__('Configurazione iniziale')}</h1>
      <div autoAnimate>
        {m(this.steps[this.currentStep()], {
          ...vnode.attrs.page.props,
          onSaveInstall: this.onSaveInstall.bind(this),
          onStepChange: (step: SetupSteps) => this.currentStep(step)
        })}
      </div>
    </>;
  }

  async onSaveInstall() {
    let data = {};
    for (const step of Object.values(this.steps)) {
      data = {...data, ...step.data};
    }

    try {
      await Request.put(route('setup.save'), data);
    } catch (error: any) {
      void showSnackbar((error as RequestError<{message: string}>).response.message);
      return;
    }

    void showSnackbar(__('Impostazioni salvate correttamente'));
    router.visit(route('login'));
  }
}
