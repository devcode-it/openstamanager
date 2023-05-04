import {
  mdiCalendarClockOutline,
  mdiCalendarMonthOutline,
  mdiClockOutline
} from '@mdi/js';
import collect from 'collect.js';
import dayjs from 'dayjs';

import {Form} from 'mithril-utilities';
import Stream from 'mithril/stream';
import MdIcon from '~/Components/MdIcon';
import {VnodeCollectionItem} from '~/typings/jsx';

import {
  SetupStep,
  SetupSteps
} from './SetupStep';

export default class RegionalSettings extends SetupStep {
  previousStep = SetupSteps.Welcome;

  nextStep = SetupSteps.Database;

  dateFormats = {
    long: Stream('DD/MM/YYYY HH:mm:ss'),
    short: Stream('DD/MM/YYYY'),
    time: Stream('HH:mm')
  };

  contents() {
    return (
      <div style={{textAlign: 'center'}}>
        <h4>{__('Formato date')}</h4>
        <p>
          {_v('I formati sono impostabili attraverso lo standard previsto da :dayjs_link.', {
            dayjs_link: <a href="https://day.js.org/docs/en/display/format">DayJS</a>
          })}
        </p>
        <Form>
          <md-layout-grid style={{'--mdc-layout-grid-margin-desktop': 0}}>
            {this.fields().toArray()}
          </md-layout-grid>
          <small style={{display: 'block', marginTop: '16px'}}>{__('* Campi obbligatori')}</small>
        </Form>
      </div>
    );
  }

  get data(): Record<string, any> {
    return {
      date_format_long: this.dateFormats.long(),
      date_format_short: this.dateFormats.short(),
      time_format: this.dateFormats.time()
    };
  }

  fields() {
    return collect<VnodeCollectionItem>({
      long_date_format: (
        // eslint-disable-next-line sonarjs/no-duplicate-string
        <md-filled-text-field name="long_date_format" label={__('Formato data lunga')} required state={this.dateFormats.long} supportingText={__('Anteprima: :date', {
          date: dayjs().format(this.dateFormats.long())
        })}>
          <MdIcon icon={mdiCalendarClockOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      short_date_format: (
        <md-filled-text-field name="short_date_format" label={__('Formato data corta')} required state={this.dateFormats.short} supportingText={__('Anteprima: :date', {
          date: dayjs().format(this.dateFormats.short())
        })}>
          <MdIcon icon={mdiCalendarMonthOutline} slot="leadingicon"/>
        </md-filled-text-field>
      ),
      time_format: (
        <md-filled-text-field name="time_format" label={__('Formato orario')} required state={this.dateFormats.time} supportingText={__('Anteprima: :date', {
          date: dayjs().format(this.dateFormats.time())
        })}
        >
          <MdIcon icon={mdiClockOutline} slot="leadingicon"/>
        </md-filled-text-field>
      )
    });
  }
}
