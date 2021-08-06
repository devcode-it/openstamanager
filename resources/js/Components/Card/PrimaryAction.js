import Component from '../Component';

export default class PrimaryAction extends Component {
  view(vnode) {
    return (
      <div class="mdc-card__primary-action" tabindex="0">
        <div class="mdc-card__ripple"/>
        {vnode.children}
      </div>
    );
  }
}
