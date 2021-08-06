import Component from '../Component';

export default class Media extends Component {
  view(vnode) {
    return (
      <div class={
        `mdc-card__media ${vnode.attrs.noscaling ? '' : 'mdc-card__media--16-9'} 
        ${vnode.attrs.square ? 'mdc-card__media--square' : ''}`
      } style={vnode.attrs.background ? `background-image: url("${vnode.attrs.background}");` : ''}>
        <div class="mdc-card__media-content">{vnode.attrs.title ?? ''}</div>
      </div>
    );
  }
}
