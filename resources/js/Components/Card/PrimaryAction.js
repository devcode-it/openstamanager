import Component from '../Component';
import '@material/mwc-ripple';

export default class PrimaryAction extends Component {
  view(vnode) {
    return (
      <div class="mdc-card__primary-action" tabindex="0">
        <mwc-ripple/>
        {vnode.children}
      </div>
    );
  }
}
