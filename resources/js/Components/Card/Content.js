import Component from '../Component';

export default class Content extends Component {
  view(vnode) {
    return (
      <div class="mdc-card__content">
        {vnode.children}
      </div>
    );
  }
}
