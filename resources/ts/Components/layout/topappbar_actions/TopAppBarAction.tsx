import MdIcon, {Attributes as MdIconAttributes} from '@osm/Components/MdIcon';
import {
  Children,
  Vnode
} from 'mithril';
import {Component} from 'mithril-utilities';

export default abstract class TopAppBarAction extends Component {
  abstract ariaLabel: string;
  abstract icon: MdIconAttributes['icon'];
  abstract id: string;

  view(vnode: Vnode): Children {
    return (
      <md-standard-icon-button id={this.id} slot="actionItems" ariaLabel={this.ariaLabel} onclick={this.callback.bind(this)}>
        <MdIcon icon={this.icon}/>
      </md-standard-icon-button>
    );
  }

  abstract callback(): void;

  getIconElement() {
    return <MdIcon icon={this.icon}/>;
  }
}
