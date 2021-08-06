import Component from '../Component';

export default class Actions extends Component {
  view(vnode) {
    return (
      <div class={`mdc-card__actions ${vnode.attrs.fullbleed ? 'mdc-card__actions--full-bleed' : ''}`}>
        {vnode.children}
      </div>
    );
  }
}
