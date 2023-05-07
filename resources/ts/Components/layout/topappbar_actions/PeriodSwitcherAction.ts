import {mdiCalendarRangeOutline} from '@mdi/js';
import TopAppBarAction from '@osm/Components/layout/topappbar_actions/TopAppBarAction';

export default class PeriodSwitcherAction extends TopAppBarAction {
  ariaLabel = __('Cambia periodo');
  icon = mdiCalendarRangeOutline;
  id = 'navbar-switch-period';

  callback(): void {}
}
