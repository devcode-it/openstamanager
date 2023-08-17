import '@material/web/textfield/filled-text-field.js';
import '@material/web/iconbutton/icon-button.js';

import {FilledTextField} from '@material/web/textfield/internal/filled-text-field';
import {mdiCalendarMonthOutline} from '@mdi/js';
import MdIcon from '@osm/Components/MdIcon';
import {Vnode} from 'mithril';
import {
  Attributes,
  Component
} from 'mithril-utilities';
import {KebabCasedProperties} from 'type-fest';

export interface FilledDateTextFieldAttributes extends Attributes, Partial<Omit<KebabCasedProperties<FilledTextField>, 'style'>> {}

export default class FilledDateTextField<A extends FilledDateTextFieldAttributes> extends Component<A> {
  element!: FilledTextField;
  view(vnode: Vnode<A>) {
    vnode.attrs.type ??= 'date';
    return (
      <md-filled-text-field {...vnode.attrs}>
        {/* @ts-ignore */}
        {vnode.children}
        <md-icon-button slot="trailingicon" onclick={this.openDatePicker.bind(this)}>
          <MdIcon icon={mdiCalendarMonthOutline}/>
        </md-icon-button>
      </md-filled-text-field>
    );
  }

  openDatePicker(event: MouseEvent & {redraw?: boolean}) {
    event.redraw = false;
    // @ts-expect-error - getInput() is private
    (this.element.getInput() as HTMLInputElement).showPicker();
  }
}
