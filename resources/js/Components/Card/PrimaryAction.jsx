import '@material/mwc-ripple';

import Component from '../Component.jsx';

export default class PrimaryAction extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdc-card__primary-action');
    return (
      <div {...this.attrs.all()} tabindex="0">
        <mwc-ripple/>
        {vnode.children}
      </div>
    );
  }
}
