import '@maicol07/material-web-additions/card/elevated-card.js';

import {router} from '@maicol07/inertia-mithril';
import Page, {PageAttributes} from '@osm/Components/Page';
import {showSnackbar} from '@osm/utils/misc';
import AdminUserStep from '@osm/Views/Setup/Steps/AdminUserStep';
import DatabaseStep from '@osm/Views/Setup/Steps/DatabaseStep';
import RegionalSettings from '@osm/Views/Setup/Steps/RegionalSettings';
import type {Vnode} from 'mithril';
import Stream from 'mithril/stream';
import {
  Request,
  RequestError
} from 'mithril-utilities';
import {match} from 'ts-pattern';
import {Class} from 'type-fest';

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
  initialStep = SetupSteps.Welcome;
  currentStep = Stream<SetupSteps>(SetupSteps.Welcome);
  steps: Record<SetupSteps, SetupStep<any>> = {
    [SetupSteps.Welcome]: new WelcomeStep(),
    [SetupSteps.RegionalSettings]: new RegionalSettings(),
    [SetupSteps.Database]: new DatabaseStep(),
    [SetupSteps.AdminUser]: new AdminUserStep()
  };

  oninit(vnode: Vnode<SetupPageAttributes, this>) {
    super.oninit(vnode);
    // @ts-expect-error
    const {step} = route().params;
    if (step) {
      const setupStep = match(step)
        .with('regional_settings', () => SetupSteps.RegionalSettings)
        .with('database', () => SetupSteps.Database)
        .with('admin_user', () => SetupSteps.AdminUser)
        .otherwise(() => SetupSteps.Welcome);
      this.currentStep(setupStep);
      this.initialStep = setupStep;
    }
  }

  contents(vnode: Vnode<SetupPageAttributes>) {
    // noinspection LocalVariableNamingConventionJS - Capitalized name is needed for JSX. Cast to unknown to avoid TS error about JSX element type
    const Step = this.steps[this.currentStep()] as unknown as Class<SetupStep>;
    return <>
      <h1>{__('Configurazione iniziale')}</h1>
      <div auto-animate>
        <Step {...vnode.attrs.page.props}
              disablePreviousButton={this.currentStep() === this.initialStep}
              onSaveInstall={this.onSaveInstall.bind(this)}
              onStepChange={this.currentStep.bind(this)}/>
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
      void showSnackbar(__('Impostazioni salvate correttamente'));
      router.visit(route('login'));
    } catch (error: any) {
      // eslint-disable-next-line no-console
      console.error(error);
      void showSnackbar((error as RequestError<{message: string}>).response.message);
    }
  }
}
