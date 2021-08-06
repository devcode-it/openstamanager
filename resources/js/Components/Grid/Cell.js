import Component from '../Component';

export default class Cell extends Component {
  view(vnode) {
    return <div
      class={
        `mdc-layout-grid__cell 
        ${vnode.attrs.columnspan ? `mdc-layout-grid__cell--span-${vnode.attrs.columnspan}` : ''} 
        ${vnode.attrs['columnspan-desktop'] ? `mdc-layout-grid__cell--span-${vnode.attrs['columnspan-desktop']}-desktop` : ''} 
        ${vnode.attrs['columnspan-tablet'] ? `mdc-layout-grid__cell--span-${vnode.attrs['columnspan-tablet']}-tablet` : ''} 
        ${vnode.attrs['columnspan-phone'] ? `mdc-layout-grid__cell--span-${vnode.attrs['columnspan-phone']}-phone` : ''} 
        ${vnode.attrs.order ? `mdc-layout-grid__cell--order-${vnode.attrs.order}` : ''} 
        ${vnode.attrs.align ? `mdc-layout-grid__cell--align-${vnode.attrs.align}` : ''} 
        ${vnode.attrs.class ?? ''}`
      }
      {...vnode.attrs}>
      {vnode.children}
    </div>;
  }
}
