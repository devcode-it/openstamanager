import {MDCRipple} from '@material/ripple';
import Mithril from 'mithril';
import Component from '../Component';

export default class Card extends Component {
  view(vnode) {
    return <div
      class={
        `mdc-card 
        ${vnode.attrs.outlined ? 'mdc-card--outlined' : ''} 
        ${vnode.attrs['columnspan-desktop'] ? `mdc-layout-grid__cell--span-${vnode.attrs['columnspan-desktop']}-desktop` : ''} 
        ${vnode.attrs['columnspan-tablet'] ? `mdc-layout-grid__cell--span-${vnode.attrs['columnspan-tablet']}-tablet` : ''} 
        ${vnode.attrs['columnspan-phone'] ? `mdc-layout-grid__cell--span-${vnode.attrs['columnspan-phone']}-phone` : ''} 
        ${vnode.attrs.order ? `mdc-layout-grid__cell--order-${vnode.attrs.order}` : ''} 
        ${vnode.attrs.align ? `mdc-layout-grid__cell--align-${vnode.attrs.align}` : ''} 
        ${vnode.attrs.class ?? ''}`
      }
      style={vnode.attrs.style ?? ''}>
      {vnode.children}
    </div>;
  }

  oncreate(vnode: Mithril.VnodeDOM) {
    super.oncreate(vnode);
    $('.mdc-card__primary-action').each((index, element) => new MDCRipple(element));
  }
}
