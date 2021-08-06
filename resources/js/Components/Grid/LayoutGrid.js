import Component from '../Component';

export default class LayoutGrid extends Component {
  view(vnode) {
    return <div
      class={
        `mdc-layout-grid ${vnode.attrs.fixed ? 'mdc-layout-grid--fixed-column-width' : ''}
        ${vnode.attrs.align ? `mdc-layout-grid--align-${vnode.attrs.align}` : ''}
        ${vnode.attrs.class}`
      }
      style={vnode.attrs.style}>
      {vnode.children}
    </div>;
  }
}
