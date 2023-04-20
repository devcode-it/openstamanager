import '@material/web/button/outlined-button.js';

import {
  mdiChevronLeft,
  mdiChevronRight
} from '@mdi/js';
import {
  Children,
  Vnode
} from 'mithril';

import {
  Attributes,
  Component
} from '~/Components/Component';
import MdIcon from '~/Components/MdIcon';


export enum SetupSteps {
  Welcome = 'welcome',
  RegionalSettings = 'regional_settings',
  Database = 'database',
  AdminUser = 'admin_user'
}

export interface SetupStepAttributes extends Attributes {
  onStepChange: (step: SetupSteps) => void;
}

export abstract class SetupStep<A extends SetupStepAttributes = SetupStepAttributes> extends Component<A> {
  previousStep: SetupSteps | undefined;
  nextStep: SetupSteps | undefined;

  view(vnode: Vnode<A, this>): Children {
    return (
      <div>
        {this.contents(vnode)}
        <div className="setup-buttons">
          {this.previousButton(vnode)}
          {this.middleButton(vnode)}
          {this.nextButton(vnode)}
        </div>
      </div>
    );
  }

  abstract contents(vnode: Vnode<A, this>): Children;

  previousButton(vnode: Vnode<A, this>): Children {
    return (
      <md-outlined-button onclick={this.onPreviousButtonClicked.bind(this, vnode)} label={__('Precedente')} disabled={!this.isPreviousButtonEnabled(vnode)}>
        <MdIcon icon={mdiChevronLeft} slot="icon"/>
      </md-outlined-button>
    );
  }

  isPreviousButtonEnabled(vnode: Vnode<A, this>): boolean {
    return Boolean(this.previousStep);
  }

  onPreviousButtonClicked(vnode: Vnode<A, this>): void {
    if (this.previousStep) {
      vnode.attrs.onStepChange(this.previousStep);
    }
  }

  middleButton(vnode: Vnode<A, this>): Children | void {
  }

  nextButton(vnode: Vnode<A, this>): Children {
    return (
      <md-outlined-button onclick={this.onNextButtonClicked.bind(this, vnode)} label={__('Prossimo')} disabled={!this.isNextButtonEnabled(vnode)} trailingIcon={true}>
        <MdIcon icon={mdiChevronRight} slot="icon"/>
      </md-outlined-button>
    );
  }

  isNextButtonEnabled(vnode: Vnode<A, this>): boolean {
    return Boolean(this.nextStep);
  }

  onNextButtonClicked(vnode: Vnode<A, this>): void {
    if (this.nextStep) {
      vnode.attrs.onStepChange(this.nextStep);
    }
  }

  abstract get data(): Record<string, any>;
}
